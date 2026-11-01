<?php

namespace App\Http\Middleware;

use Closure;
use Hop\Bio\Models\BioPage;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel's session middleware, except that a first-time reader of a public bio page does not get a session.
 * Starting one writes a row to the sessions table and sets two cookies for every visitor, and a page that has no form,
 * poll, password or age gate has nothing to remember. Such a visit gets a throw-away in-memory session and no cookies.
 * A visitor who already has a session cookie, and every other route, behaves exactly as before.
 */
class OptionalSession extends StartSession
{
    /** How long "this page needs no session" is remembered; saving a block or the page forgets it at once. */
    public const REMEMBER_SECONDS = 60;

    public function handle($request, Closure $next)
    {
        if (! $this->statelessVisit($request)) {
            return parent::handle($request, $next);
        }
        $session = new Store((string) config('session.cookie'), new ArraySessionHandler(1));
        $session->start();
        $request->setLaravelSession($session);
        $response = $next($request);
        // the CSRF middleware adds its token cookie to every response; a reader with no forms does not need it
        $response->headers->removeCookie('XSRF-TOKEN', config('session.path', '/'), config('session.domain'));
        $response->headers->removeCookie((string) config('session.cookie'), config('session.path', '/'), config('session.domain'));

        return $response;
    }

    private function statelessVisit(Request $request): bool
    {
        $name = $request->route()?->getName();
        if (! $request->isMethod('GET') || ! in_array($name, ['bio.public.show', 'bio.public.shop'], true)
            || $request->hasCookie((string) config('session.cookie'))) {
            return false;
        }
        $slug = strtolower((string) $request->route('slug'));

        return $slug !== '' && self::pageNeedsNoSession($slug);
    }

    /** No password, no content warning, and no form or poll block (both need a CSRF token): a plain page. */
    public static function pageNeedsNoSession(string $slug): bool
    {
        return Cache::remember('bio-stateless:'.$slug, self::REMEMBER_SECONDS, function () use ($slug) {
            $page = BioPage::where('slug', $slug)->first();
            if (! $page || $page->password_enabled || $page->requiresContentWarning()) {
                return false;
            }

            return ! $page->links()->whereIn('type', ['form', 'poll'])->exists();
        });
    }

    public static function forget(string $slug): void
    {
        Cache::forget('bio-stateless:'.strtolower($slug));
    }
}
