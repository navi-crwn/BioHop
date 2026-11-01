<?php

namespace Hop\Bio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TurnstileService;
use App\Support\QrPng;
use App\Support\VisitorHash;
use Hop\Bio\Contracts\GeoLookup;
use Hop\Bio\Contracts\UserAgentParser;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioPollVote;
use Hop\Bio\Support\ImageMeta;
use Hop\Safety\SafeContent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

use function Illuminate\Support\defer;

class PublicBioController extends Controller
{
    private const VISITOR_COOKIE = 'hel_vid';

    private const COUNTED_CLICKS_PER_IP = 20;

    /** hel.ink/b/shop/{slug}: the same page with the Shop open; a page that sells nothing goes to its links. */
    public function shop(Request $request, string $slug)
    {
        $page = BioPage::where('slug', $slug)->first();
        if (! $page || ! $page->links()->active()->where('type', 'product')->exists()) {
            return redirect()->route('bio.public.show', $slug);
        }
        $request->attributes->set('openShop', true);

        return $this->show($request, $slug);
    }

    public function show(Request $request, string $slug)
    {
        $bioPage = BioPage::with(['links' => function ($query) {
            $query->active()->ordered();
        }])
            ->where('slug', $slug)
            ->first();
        // Page not found or not published
        if (! $bioPage) {
            return response()->view('bio::not-found', ['slug' => $slug], 404);
        }
        if (! $this->isVisible($bioPage)) {
            // The owner can send visitors of an unpublished page somewhere else (a real address, checked on save).
            if ($bioPage->fallback_url && $bioPage->is_published === false && ! $bioPage->user?->isSuspended() && ! $request->attributes->get('hop.embed')) {
                return redirect()->away(SafeContent::url($bioPage->fallback_url))->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            }

            return response()->view('bio::not-found', ['slug' => $slug, 'unpublished' => true], 404);
        }
        // Password protection check
        if ($this->needsPassword($bioPage)) {
            $sessionKey = "bio_unlocked_{$bioPage->id}";
            if ($request->isMethod('post') && $request->has('password')) {
                $key = 'bio-password:'.$bioPage->id.':'.$request->ip();
                if (RateLimiter::tooManyAttempts($key, 10)) {
                    return view('bio::password', compact('bioPage'))->withErrors(['password' => 'Too many attempts. Try again later.']);
                }
                RateLimiter::hit($key, 600);
                if ($this->passwordMatches($request->input('password'), $bioPage->password)) {
                    RateLimiter::clear($key);
                    session([$sessionKey => true]);

                    // Use 303 redirect (See Other) to convert POST to GET - prevents browser resubmit warning
                    return redirect()->to(url()->current(), 303);
                } else {
                    return view('bio::password', compact('bioPage'))->withErrors(['password' => 'Incorrect password']);
                }
            } else {
                return view('bio::password', compact('bioPage'));
            }
        }
        // Content warning & 18+ Age verification check
        if ($this->needsAgeCheck($bioPage, $request)) {
            $sessionKey = "bio_age_verified_{$bioPage->id}";
            if ($request->isMethod('post') && in_array($request->input('age_confirm'), ['yes', '1', 'continue'], true)) {
                session([$sessionKey => true]);

                $response = redirect()->to(url()->current(), 303);
                if ($request->boolean('remember')) {
                    $response->withCookie(new Cookie("hop_gate_bio_{$bioPage->id}", '1', now()->addDay(), '/', null, $request->isSecure(), true, false, 'lax'));
                }

                return $response;
            } else {
                return view('bio::age-verify', compact('bioPage'));
            }
        }
        // A copy of the page inside another page (the BioHop home) is not a visit.
        if (! $request->attributes->get('hop.embed')) {
            $this->trackView($bioPage);
        }

        return view('bio::show', compact('bioPage'));
    }

    public function click(BioLink $bioLink)
    {
        // Disabled blocks / hidden pages must not keep redirecting.
        $bioPage = $bioLink->bioPage;
        $ua = app(UserAgentParser::class)->parse(request()->userAgent());
        // A WhatsApp rotator picks one of its numbers per click, an app block the store for the visitor's system; other blocks go to their address.
        $target = match ($bioLink->type) {
            'whatsapp' => $bioLink->whatsappTarget() ?? '#',
            'app' => $bioLink->appTarget((string) ($ua['os'] ?? '')) ?? '#',
            default => SafeContent::url($bioLink->url),
        };
        if (! $bioLink->is_active || $bioLink->archived_at || ! $bioLink->isLive() || ! $this->isVisible($bioPage) || $target === '#') {
            abort(404);
        }
        // Rules on a link (country, device, system, hours) can send this visitor somewhere else; the first match wins.
        if ($bioLink->type === 'link' && $bioLink->routingRules() !== []) {
            $zone = $bioPage->user?->timezone ?: config('app.timezone');
            try {
                $now = now($zone);
            } catch (\Throwable) {
                $now = now();
            }
            $routed = $bioLink->routedUrl($ua, fn () => $this->getCountryFromIp(request()->ip()), $now);
            if ($routed && SafeContent::url($routed) !== '#') {
                $target = SafeContent::url($routed);
            }
        }
        if ($bioPage->auto_utm) {
            $target = self::withUtm($target, $bioPage->slug);
        }
        // Same gates as show(): never reveal a destination behind a password or age check.
        if ($this->needsPassword($bioPage) || $this->needsAgeCheck($bioPage)) {
            return redirect()->route('bio.public.show', $bioPage->slug);
        }
        // A link with its own PIN asks for it once per session.
        if ($this->needsPin($bioLink)) {
            return response()->view('bio::link-pin', compact('bioLink'));
        }
        $ipAddress = request()->ip();
        $visitorId = $this->visitorId();
        $newVisitorId = $visitorId === null && ! $ua['bot'] ? Str::random(32) : null;
        // Visitors always reach the destination; limits only decide whether the click is counted.
        // Dedup by visitor cookie (people sharing one IP still count separately), falling back to IP + User-Agent.
        // At most 20 counted clicks per IP per block per 10 minutes, so clearing cookies cannot inflate counts.
        $dedupKey = 'bio_click:'.$bioLink->id.':'.($visitorId ?? sha1($ipAddress.'|'.request()->userAgent()));
        $ipKey = "bio_click_ip:{$bioLink->id}:{$ipAddress}";
        if (! $ua['bot'] && ! $bioPage->privacy_mode
            && ! RateLimiter::tooManyAttempts($ipKey, self::COUNTED_CLICKS_PER_IP)
            && Cache::add($dedupKey, true, now()->addMinutes(10))) {
            RateLimiter::hit($ipKey, 600);
            $click = [
                'ip_address' => $ipAddress,
                'device' => strtolower($ua['device']),
                'browser' => $ua['browser'],
                'referrer' => request()->header('referer'),
                'source' => in_array(request()->query('s'), self::SOURCES, true) ? request()->query('s') : null,
            ];
            // The country lookup can take seconds for a new IP; record after the redirect is sent.
            defer(fn () => $bioLink->recordClick($click + ['country' => $this->getCountryFromIp($ipAddress)]));
        }
        $response = redirect()->away($target)->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        if ($newVisitorId !== null) {
            // The click just made under IP + User-Agent also covers the new cookie, so it is not counted twice.
            Cache::put("bio_click:{$bioLink->id}:{$newVisitorId}", true, now()->addMinutes(10));
            $response->withCookie(cookie(self::VISITOR_COOKIE, $newVisitorId, 60 * 24 * 365, null, null, null, true, false, 'lax'));
        }

        return $response;
    }

    /**
     * Anonymous random id used only to de-duplicate clicks; null when the visitor has none yet.
     */
    protected function visitorId(): ?string
    {
        $id = request()->cookie(self::VISITOR_COOKIE);

        return is_string($id) && preg_match('/^[A-Za-z0-9]{32}$/', $id) ? $id : null;
    }

    public function qr(string $slug)
    {
        $bioPage = BioPage::where('slug', $slug)->first();
        abort_unless($this->isVisible($bioPage), 404);
        $url = public_url('b/'.$bioPage->slug).'?src=qr';
        $qrCode = QrPng::render($url, 512, margin: 2);

        return response($qrCode)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', "attachment; filename=\"{$bioPage->slug}-qr.png\"");
    }

    /** The page as an installable web app: a name, an icon, and a start address that counts those visits as "pwa". */
    public function manifest(string $slug)
    {
        $bioPage = BioPage::where('slug', $slug)->first();
        abort_unless($this->isVisible($bioPage), 404);
        $pick = function (?string $value) {
            return $value && preg_match('/#[0-9a-fA-F]{6}\b/', $value, $m) ? $m[0] : null;
        };
        $themes = config('bio-themes.presets');
        $theme = $themes[$bioPage->theme] ?? null;
        $color = $pick($bioPage->background_value) ?: $pick($bioPage->background_color) ?: $pick($bioPage->background_gradient) ?: $pick($theme['background'] ?? null) ?: '#ffffff';
        $icons = [];
        if ($bioPage->avatar_url && ($meta = ImageMeta::for($bioPage->avatar_url))) {
            $ext = strtolower(pathinfo($bioPage->avatar_url, PATHINFO_EXTENSION));
            $icons[] = ['src' => Storage::disk('public')->url($bioPage->avatar_url), 'sizes' => $meta['w'].'x'.$meta['h'], 'type' => ['webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif'][$ext] ?? 'image/png', 'purpose' => 'any'];
        }

        return response()->json([
            'name' => $bioPage->title,
            'short_name' => Str::limit($bioPage->title, 12, ''),
            'start_url' => '/b/'.$bioPage->slug.'?src=pwa',
            'scope' => '/b/'.$bioPage->slug,
            'display' => 'standalone',
            'background_color' => $color,
            'theme_color' => $color,
            'icons' => $icons,
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=300']);
    }

    /** Adds utm_source/medium/campaign to an http(s) address that has no UTM parameters of its own. */
    public static function withUtm(string $url, string $slug): string
    {
        if (! preg_match('#^https?://#i', $url) || preg_match('/[?&]utm_[a-z]+=/i', $url)) {
            return $url;
        }
        $query = http_build_query(['utm_source' => 'biohop', 'utm_medium' => 'bio', 'utm_campaign' => $slug]);
        [$base, $fragment] = array_pad(explode('#', $url, 2), 2, null);

        return $base.(str_contains($base, '?') ? '&' : '?').$query.($fragment !== null ? '#'.$fragment : '');
    }

    protected function isVisible(?BioPage $bioPage): bool
    {
        return $bioPage && $bioPage->is_published && ! $bioPage->user?->isSuspended();
    }

    protected function needsPassword(BioPage $bioPage): bool
    {
        return $bioPage->password_enabled && $bioPage->password && ! session("bio_unlocked_{$bioPage->id}");
    }

    protected function needsAgeCheck(BioPage $bioPage, ?Request $request = null): bool
    {
        if (! $bioPage->requiresContentWarning()) {
            return false;
        }

        $sessionKey = "bio_age_verified_{$bioPage->id}";
        if (session($sessionKey)) {
            return false;
        }

        $req = $request ?? request();
        if ($req && $req->cookie("hop_gate_bio_{$bioPage->id}")) {
            return false;
        }

        return true;
    }

    /** POST /b/poll/{public_id}: one vote per device (keyed hash of address and browser); a second try just shows the results. */
    public function vote(Request $request, BioLink $bioLink)
    {
        $bioPage = $bioLink->bioPage;
        if ($bioLink->type !== 'poll' || ! $bioLink->is_active || $bioLink->archived_at || ! $bioLink->isLive() || ! $this->isVisible($bioPage) || $bioLink->pollOptions() === []) {
            abort(404);
        }
        if ($this->needsPassword($bioPage) || $this->needsAgeCheck($bioPage)) {
            return redirect()->route('bio.public.show', $bioPage->slug);
        }
        $back = redirect()->to(route('bio.public.show', $bioPage->slug).'#poll-'.$bioLink->public_id, 303);
        $count = count($bioLink->pollOptions());
        $choice = $request->input('option');
        if (! is_numeric($choice) || (int) $choice < 0 || (int) $choice >= $count || ($bioLink->pollVotes()->count() >= 100000)) {
            return $back;
        }
        if (! $request->attributes->get('hop.embed') && ! app(UserAgentParser::class)->parse($request->userAgent())['bot']) {
            try {
                $bioLink->pollVotes()->create(['option_index' => (int) $choice, 'voter_hash' => BioPollVote::voterHash($request->ip(), $request->userAgent())]);
            } catch (UniqueConstraintViolationException) {
                // already voted from this device
            }
        }

        return $back;
    }

    /** Where a visit came from, set by ?src= on the page address and carried to the clicks as ?s= */
    public const SOURCES = ['qr', 'nfc', 'pwa'];

    public const MAX_LEADS_PER_FORM = 5000;

    /** POST /b/form/{public_id}: stores one entry of a form block, then goes back to the page with a thank-you. */
    public function submitForm(Request $request, BioLink $bioLink, TurnstileService $turnstile)
    {
        $bioPage = $bioLink->bioPage;
        if ($bioLink->type !== 'form' || ! $bioLink->is_active || $bioLink->archived_at || ! $bioLink->isLive() || ! $this->isVisible($bioPage)) {
            abort(404);
        }
        if ($this->needsPassword($bioPage) || $this->needsAgeCheck($bioPage)) {
            return redirect()->route('bio.public.show', $bioPage->slug);
        }
        $back = redirect()->to(route('bio.public.show', $bioPage->slug).'#form-'.$bioLink->public_id, 303);
        // a hidden field only bots fill in: act as if it worked
        if ($request->filled('website')) {
            return $back->with('form_sent', $bioLink->public_id);
        }
        $key = 'bio-form:'.$bioLink->id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $back->withErrors(['form' => __('Too many attempts. Try again later.')], 'form_'.$bioLink->public_id);
        }
        RateLimiter::hit($key, 600);
        if (! $turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            return $back->withErrors(['form' => __('The security check failed. Please try again.')], 'form_'.$bioLink->public_id);
        }
        $fields = $bioLink->formFields();
        $rules = [
            'name' => ($fields['name'] ?? false) ? 'required|string|max:100' : 'nullable|string|max:100',
            'email' => ($fields['email'] ?? false) ? 'required|email|max:150' : 'nullable|email|max:150',
            'phone' => ($fields['phone'] ?? false) ? ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ().-]{6,30}$/'] : ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ().-]{6,30}$/'],
            'consent' => 'accepted',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $back->withErrors($validator, 'form_'.$bioLink->public_id)->withInput();
        }
        if ($bioLink->leads()->count() >= self::MAX_LEADS_PER_FORM) {
            return $back->withErrors(['form' => __('This form is not taking more entries right now.')], 'form_'.$bioLink->public_id);
        }
        $data = $validator->validated();
        $bioLink->leads()->create([
            'name' => isset($fields['name']) ? ($data['name'] ?? null) : null,
            'email' => isset($fields['email']) ? ($data['email'] ?? null) : null,
            'phone' => isset($fields['phone']) ? ($data['phone'] ?? null) : null,
            'ip_hash' => VisitorHash::make($request->ip()),
        ]);

        return $back->with('form_sent', $bioLink->public_id);
    }

    protected function needsPin(BioLink $bioLink): bool
    {
        $pin = (string) ($bioLink->settings['pin'] ?? '');

        return $pin !== '' && ! session("bio_link_unlocked_{$bioLink->id}");
    }

    /** POST /b/click/{public_id}/pin: checks the link's PIN, remembers it for the session, then redirects to the click route. */
    public function pin(Request $request, BioLink $bioLink)
    {
        $pin = (string) ($bioLink->settings['pin'] ?? '');
        if ($pin === '' || ! $bioLink->is_active || ! $this->isVisible($bioLink->bioPage)) {
            abort(404);
        }
        $key = 'bio-link-pin:'.$bioLink->id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->view('bio::link-pin', compact('bioLink'))->withErrors(['pin' => __('Too many attempts. Try again later.')]);
        }
        RateLimiter::hit($key, 600);
        if (! hash_equals($pin, (string) $request->input('pin'))) {
            return view('bio::link-pin', compact('bioLink'))->withErrors(['pin' => __('Wrong PIN.')]);
        }
        RateLimiter::clear($key);
        session(["bio_link_unlocked_{$bioLink->id}" => true]);

        return redirect()->route('bio.public.click', $bioLink->public_id, 303);
    }

    protected function trackView(BioPage $bioPage): void
    {
        if ($bioPage->privacy_mode) {
            return;
        }
        $ipAddress = request()->ip();
        $cacheKey = "bio_view:{$bioPage->id}:{$ipAddress}";
        if (! Cache::has($cacheKey)) {
            $bioPage->incrementViews();
            Cache::put($cacheKey, true, now()->addDay());
        }
    }

    /**
     * Verify a submitted bio-page password against the stored value.
     *
     * Supports hashed passwords (Hash::check) and falls back to a
     * constant-time comparison for legacy plaintext rows.
     */
    protected function passwordMatches(?string $input, ?string $stored): bool
    {
        if ($input === null || $stored === null || $stored === '') {
            return false;
        }
        if (Str::startsWith($stored, ['$2y$', '$2a$', '$2b$', '$argon2i$', '$argon2id$'])) {
            return Hash::check($input, $stored);
        }

        return hash_equals($stored, $input);
    }

    protected function getCountryFromIp(string $ipAddress): ?string
    {
        try {
            return app(GeoLookup::class)->country($ipAddress);
        } catch (\Exception $e) {
            return null;
        }
    }
}
