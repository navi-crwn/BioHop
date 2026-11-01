<?php

namespace App\Console\Commands;

use App\Bio\OfficialPage as Official;
use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Ui\Assets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Write the official HEL.ink page (hel.ink/b/hel): every public HOP app from the shared manifest, the ways
 * to start, and a short FAQ. The BioHop home shows this page live inside a phone, so it is the family's
 * front door. Blocks written here are marked `settings.official`; anything added by hand is left alone.
 */
class OfficialPage extends Command
{
    public const AVATAR = 'avatars/official-hel.png';

    protected $signature = 'bio:official-page {--apply : Write the page (without it, only show what would change)} {--user= : Owner e-mail when the page does not exist yet}';

    protected $description = 'Fill the official HEL.ink bio page with every HOP app, the ways to start, and a FAQ';

    public function handle(): int
    {
        $slug = Official::slug();
        $page = BioPage::where('slug', $slug)->first();
        if (! $page) {
            $owner = $this->option('user') ? User::where('email', $this->option('user'))->first() : null;
            if (! $owner) {
                $this->error("No page '{$slug}' yet: pass --user=<e-mail> of the account that should own it.");

                return self::FAILURE;
            }
            $page = new BioPage(['user_id' => $owner->id, 'slug' => $slug]);
        }

        $fields = self::fields();
        $blocks = self::blocks();
        $this->line(($page->exists ? 'Update' : 'Create')." hel.ink/b/{$slug}: ".count($blocks).' blocks');
        foreach ($blocks as $i => $block) {
            $this->line(sprintf('  %2d. %-8s %s', $i + 1, $block['type'], $block['title'] ?? ''));
        }
        if (! $this->option('apply')) {
            $this->info('Dry run. Add --apply to write it.');

            return self::SUCCESS;
        }

        // the avatar is a file on the public disk, like an uploaded one (the page renders Storage::url of it)
        Storage::disk('public')->put(self::AVATAR, (string) file_get_contents(public_path('images/brand/rabbit-mark.png')));
        $page->fill($fields + ['avatar_url' => self::AVATAR])->save();
        BioLink::where('bio_page_id', $page->id)->where('settings->official', true)->delete();
        $start = (int) BioLink::where('bio_page_id', $page->id)->max('order') + 1;
        foreach ($blocks as $i => $block) {
            BioLink::create($block + ['bio_page_id' => $page->id, 'order' => $start + $i, 'is_active' => true, 'settings' => ['official' => true]]);
        }
        Cache::forget('official-bio-live');
        $this->info("Written: hel.ink/b/{$slug} with ".count($blocks).' official blocks.');

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    public static function fields(): array
    {
        return [
            'title' => 'HEL.ink',
            'bio' => 'Short links, bio pages, images and private notes. One free HOP ID, open source, yours to host.',
            'theme' => 'galaxy',
            'button_shape' => 'pill',
            'social_links' => [
                ['platform' => 'github', 'value' => 'https://github.com/navi-crwn', 'enabled' => true],
                ['platform' => 'website', 'value' => 'https://hel.ink', 'enabled' => true],
            ],
            'seo_title' => 'HEL.ink · every HOP app in one place',
            'seo_description' => 'LinkHop, BioHop, PixelHop and NoteHop: short links, bio pages, images and private notes with one free HOP ID.',
            'is_published' => true,
            'is_public' => true,
            'password_enabled' => false,
            'content_warning' => 'none',
        ];
    }

    /** The blocks, top to bottom. @return list<array<string, mixed>> */
    public static function blocks(): array
    {
        $blocks = [['type' => 'header', 'title' => 'Hop in']];
        foreach (Assets::family() as $app) {
            $blocks[] = ['type' => 'link', 'title' => $app['name'], 'url' => $app['url'], 'thumbnail_url' => $app['icons']['mark']['url'] ?? null];
        }
        $blocks[] = ['type' => 'divider', 'title' => ''];
        $blocks[] = ['type' => 'header', 'title' => 'What a page can hold'];
        $blocks[] = ['type' => 'text', 'title' => 'About', 'content' => "This page is made with BioHop and shows what one page can carry: links with icons, a picture, a video, a player, a map, a contact card and a FAQ.\nEverything here is free, open source and signed in with one HOP ID."];
        $blocks[] = ['type' => 'image', 'title' => 'The HOP rabbit', 'content' => bio_media_url('/images/brand/rabbit-mark.png')];
        if ($video = self::youtubeEmbed((string) config('hop.official_video'))) {
            $blocks[] = ['type' => 'youtube', 'title' => 'Watch', 'url' => (string) config('hop.official_video'), 'embed_url' => $video];
        }
        if ($music = self::spotifyEmbed((string) config('hop.official_music'))) {
            $blocks[] = ['type' => 'spotify', 'title' => 'Listen', 'url' => (string) config('hop.official_music'), 'embed_url' => $music];
        }
        $blocks[] = ['type' => 'map', 'title' => 'Made in Indonesia', 'map_address' => 'Jakarta, Indonesia', 'map_zoom' => 10];
        $blocks[] = ['type' => 'vcard', 'title' => 'Save our contact', 'vcard_name' => 'HEL.ink', 'vcard_company' => 'HOP, by navi-crwn', 'vcard_email' => (string) config('hop.official_contact') ?: null];
        $blocks[] = ['type' => 'header', 'title' => 'Start here'];
        $blocks[] = ['type' => 'link', 'title' => 'Create your free HOP ID', 'url' => rtrim((string) config('hop.hub_url'), '/').'/register', 'description' => 'One account signs you in to every app.'];
        $blocks[] = ['type' => 'link', 'title' => 'Source code on GitHub', 'url' => 'https://github.com/navi-crwn', 'description' => 'MIT licensed. Read it, report an issue, or self-host.'];
        $blocks[] = ['type' => 'link', 'title' => 'Status and uptime', 'url' => 'https://hel.ink/status', 'description' => 'Every app, checked around the clock.'];
        $blocks[] = ['type' => 'faq', 'title' => 'Good questions', 'faq_items' => [
            ['question' => 'Is it free?', 'answer' => 'Yes. No paid plan, no locked feature, no ads. The code is MIT licensed.'],
            ['question' => 'Do I need an account?', 'answer' => 'Not to shorten a link or share an image. With a free HOP ID you keep your links, get analytics, folders, the API, a bio page and encrypted notes.'],
            ['question' => 'Can I host it myself?', 'answer' => 'Yes. Every app is open source; each repository has a self-host guide.'],
        ]];

        return $blocks;
    }

    /** A YouTube watch, short or embed address as an embed address; null when it is none of those. */
    public static function youtubeEmbed(string $url): ?string
    {
        return preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $url, $m) ? 'https://www.youtube.com/embed/'.$m[1].'?rel=0' : null;
    }

    /** A Spotify track, album, playlist or episode address as an embed address; null otherwise. */
    public static function spotifyEmbed(string $url): ?string
    {
        return preg_match('~open\.spotify\.com/(track|album|playlist|episode|show)/([A-Za-z0-9]+)~', $url, $m) ? 'https://open.spotify.com/embed/'.$m[1].'/'.$m[2] : null;
    }
}
