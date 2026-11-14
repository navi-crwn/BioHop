<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

/*
| Dashboard polish: the retired block kinds (the picker drops five, the rotator can no longer be made, old blocks
| still work), the announcement, the family line in the sidebar, the back links of a signed-in member, the sections
| of the Settings tab, and the Music block.
*/

function polishPage(string $slug = 'polish'): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Polish', 'is_published' => true]);

    return [$user, $page];
}

function dom(string $html): DOMXPath
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOERROR);

    return new DOMXPath($dom);
}

test('the block picker no longer offers the five retired kinds, and offers one Music block', function () {
    $js = file_get_contents(base_path('resources/js/editor.js'));
    preg_match('/const BLOCK_CATALOG = \[(.*?)\n\];/s', $js, $m);
    $catalog = $m[1] ?? '';
    foreach (['whatsapp', 'app', 'soundcloud', 'rss', 'poll', 'spotify'] as $retired) {
        expect($catalog)->not->toContain("['".$retired."',");
    }
    expect($catalog)->toContain("['music', 'Music'");
    // the catalogue is also the command palette's list: one list, so one removal
    expect($js)->toContain("cmds.push({ group: 'Add a block'");
});

test('a Music block takes Spotify, SoundCloud, Apple Music and YouTube Music and plays on the public page', function () {
    [$user, $page] = polishPage('polish-music');
    $this->actingAs($user);
    foreach (['https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC', 'https://soundcloud.com/forss/flickermood', 'https://music.apple.com/us/album/x/1', 'https://music.youtube.com/watch?v=abc'] as $url) {
        $this->postJson('/bio/polish-music/links', ['type' => 'music', 'title' => 'Tune', 'embed_url' => $url])->assertOk();
    }
    expect(BioLink::where('bio_page_id', $page->id)->where('type', 'music')->count())->toBe(4);
    $this->get('/b/polish-music')->assertOk()->assertSee('Tune');
    // the form for it is in the editor, with its own label
    $this->get('/bio/polish-music/edit')->assertOk()->assertSee("editingBlock?.type === 'music'", false)->assertSee('Music URL');
});

test('blocks of the retired kinds that exist already can still be edited, saved and seen', function () {
    [$user, $page] = polishPage('polish-old');
    $this->actingAs($user);
    $make = fn (string $type, array $extra) => BioLink::create(['bio_page_id' => $page->id, 'type' => $type, 'title' => 'Old '.$type, 'order' => 0, 'is_active' => true] + $extra);
    $old = [
        $make('whatsapp', ['brand' => 'whatsapp', 'settings' => ['mode' => 'round_robin', 'numbers' => [['number' => '6281200000001']]]]),
        $make('app', ['url' => 'https://apps.apple.com/app/id284882215', 'settings' => ['ios' => 'https://apps.apple.com/app/id284882215']]),
        $make('soundcloud', ['embed_url' => 'https://soundcloud.com/forss/flickermood']),
        $make('rss', ['url' => 'https://example.com/feed.xml', 'settings' => ['count' => 3]]),
        $make('poll', ['settings' => ['options' => [['text' => 'Blue'], ['text' => 'Green']]]]),
    ];
    foreach ($old as $block) {
        $this->putJson('/bio/polish-old/links/'.$block->id, ['type' => $block->type, 'title' => 'Renamed '.$block->type])->assertOk();
        expect($block->fresh()->title)->toBe('Renamed '.$block->type);
    }
    $this->get('/b/polish-old')->assertOk()->assertSee('Renamed whatsapp')->assertSee('Renamed poll');
    // the editor still opens the forms of all five, and Imperium still reads the kind names
    $editor = $this->get('/bio/polish-old/edit')->assertOk()->getContent();
    foreach (['whatsapp', 'app', 'soundcloud', 'rss', 'poll'] as $type) {
        expect($editor)->toContain("editingBlock?.type === '".$type."'");
    }
    expect(array_keys(BioLink::TYPE_NAMES))->toContain('whatsapp', 'app', 'soundcloud', 'rss', 'poll');
});

test('the retired-rotator notice shows only to members who have such a block, can be hidden, and names a date only when one is set', function () {
    [$owner, $page] = polishPage('polish-note');
    [$other] = polishPage('polish-note-2');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'whatsapp', 'title' => 'Chat', 'order' => 0, 'is_active' => true, 'settings' => ['numbers' => [['number' => '6281200000001']]]]);

    $without = $this->actingAs($other)->get(route('bio.index'))->assertOk()->getContent();
    expect($without)->not->toContain('data-announcement');

    $html = $this->actingAs($owner)->get(route('bio.index'))->assertOk()->getContent();
    expect($html)->toContain('data-announcement')->toContain('data-hop-remind="whatsapp-rotator-retired"')->toContain('The WhatsApp rotator block is being retired.')->not->toContain('keep working until');
    // the card is the first thing in the content, above the page's own sections
    $x = dom($html);
    expect($x->query('//main//*[@data-announcement]'))->toHaveCount(1);
    expect($x->query('//main//*[@data-announcement]/preceding::*[@data-checklist]'))->toHaveCount(0);

    config(['hop.whatsapp_retire_on' => '2027-03-01']);
    $this->get(route('bio.index'))->assertSee('keep working until 1 March 2027');

    // it is on the dashboard home only: the editor, the settings and the analytics do not carry it, and "Hide" hides it for a week
    foreach (['/bio/polish-note/edit', route('settings'), '/bio/polish-note/analytics'] as $other) {
        $this->get($other)->assertDontSee('data-announcement', false);
    }
    $this->postJson(route('reminders.dismiss'), ['key' => 'whatsapp-rotator-retired'])->assertOk();
    $this->get(route('bio.index'))->assertDontSee('data-announcement', false);
    $this->travel(8)->days();
    $this->get(route('bio.index'))->assertSee('data-announcement', false);
    $this->travelBack();
    $this->postJson(route('reminders.dismiss'), ['key' => 'made-up'])->assertStatus(422);
});

test('the family line is the last thing in the sidebar and appears once, and not under the content', function () {
    [$user] = polishPage('polish-family');
    foreach ([route('bio.index'), '/bio/polish-family/edit', route('settings')] as $url) {
        $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
        $x = dom($html);
        expect($x->query('//*[contains(@class, "hop-family-row")]'))->toHaveCount(1);
        expect($x->query('//aside//*[contains(@class, "hop-family-row")]'))->toHaveCount(1);
        expect($x->query('//footer//*[contains(@class, "hop-family-row")]'))->toHaveCount(0);
        // nothing follows it inside the sidebar panel
        expect($x->query('//aside//*[contains(@class, "hop-family-side")]/following-sibling::*'))->toHaveCount(0);
    }
    // the public footer keeps its own line
    $this->get('/about')->assertOk()->assertSee('hop-family-row', false);
});

test('a signed-in member is led back to the dashboard, a visitor to the home page', function () {
    [$user] = polishPage('polish-back');
    $dash = route('bio.index');
    $home = route('home');
    foreach (['/about', '/help', '/privacy', '/changelog', '/report-abuse'] as $path) {
        $member = dom($this->actingAs($user)->get($path)->assertOk()->getContent());
        $back = $member->query('//a[contains(normalize-space(.), "Back to dashboard")]');
        expect($back)->toHaveCount(1, $path);
        expect($back->item(0)->getAttribute('href'))->toBe($dash);
        expect(dom($this->actingAs($user)->get($path)->getContent())->query('//a[contains(normalize-space(.), "Back to BioHop")]'))->toHaveCount(0);
    }
    auth()->logout();
    foreach (['/about', '/help', '/report-abuse'] as $path) {
        $guest = dom($this->get($path)->assertOk()->getContent());
        $back = $guest->query('//a[contains(normalize-space(.), "Back to BioHop")]');
        expect($back)->toHaveCount(1, $path);
        expect($back->item(0)->getAttribute('href'))->toBe($home);
        expect($guest->query('//a[contains(normalize-space(.), "Back to dashboard")]'))->toHaveCount(0);
    }
    // the header of the public pages: the Dashboard button and the logo go to the dashboard of this app, not to the hub
    $member = $this->actingAs($user)->get('/about')->getContent();
    $x = dom($member);
    expect($x->query('//header//a[normalize-space(.) = "Dashboard"]')->item(0)->getAttribute('href'))->toBe($dash);
    expect($x->query('//header//a[@aria-label="BioHop by hel.ink"]')->item(0)->getAttribute('href'))->toBe($dash);
    auth()->logout();
    expect(dom($this->get('/about')->getContent())->query('//header//a[@aria-label="BioHop by hel.ink"]')->item(0)->getAttribute('href'))->toBe($home);
});

test('the Settings tab is four sections, and every card sits in one', function () {
    [$user] = polishPage('polish-settings');
    $html = $this->actingAs($user)->get('/bio/polish-settings/edit')->assertOk()->getContent();
    $x = dom($html);
    $cards = $x->query('//*[@data-settings-section]');
    expect($cards->length)->toBe(9); // SEO, Sharing, Privacy, Analytics, Webhook, Versions, Floating button, Footer, Danger zone
    $sections = collect($cards)->map(fn ($n) => $n->getAttribute('data-settings-section'))->unique()->sort()->values()->all();
    expect($sections)->toBe(['privacy', 'safety', 'search', 'tools']);
    // a refused field opens the section that holds it: its input is inside the card of that section
    foreach (['seo_title' => 'search', 'seo_description' => 'search', 'webhook_url' => 'tools', 'footer_note' => 'tools'] as $field => $section) {
        $input = $x->query('//*[@x-model="bioPage.'.$field.'"]');
        expect($input->length)->toBeGreaterThan(0, $field);
        $card = $x->query('//*[@x-model="bioPage.'.$field.'"]/ancestor::*[@data-settings-section]')->item(0);
        expect($card?->getAttribute('data-settings-section'))->toBe($section, $field);
    }
});

test('the block form has sections, and the new messages exist in all nine languages', function () {
    $keys = [
        'Content', 'Look', 'Schedule', 'Advanced', 'Block sections', 'Music URL', 'Music URL is required',
        'Spotify, SoundCloud, Apple Music or YouTube Music', 'Paste a Spotify, SoundCloud, Apple Music or YouTube Music link',
        'Please enter a Spotify, SoundCloud, Apple Music or YouTube Music URL', 'The end time must be after the time it goes live.',
        'This page was changed in another tab or window. Reload the editor to get the latest version.',
        'The shape is not used by this header style.', 'Position', 'Search & sharing', 'Tools', 'Versions', 'Settings sections', 'Notice',
        'The WhatsApp rotator block is being retired.', 'Back to dashboard', 'Back',
        'The blocks you already have keep working for now, but new ones cannot be added. Use a normal link block or a LinkHop link instead.',
        'The blocks you already have keep working until :date, but new ones cannot be added. Use a normal link block or a LinkHop link instead.',
    ];
    foreach (['zh-CN', 'de', 'id', 'ja', 'pt-BR', 'es', 'fr', 'ru'] as $locale) {
        $lang = json_decode(file_get_contents(lang_path($locale.'.json')), true);
        foreach ($keys as $key) {
            expect($lang)->toHaveKey($key);
            preg_match_all('/:\w+/', $key, $want);
            preg_match_all('/:\w+/', $lang[$key], $got);
            expect(collect($got[0])->sort()->values()->all())->toBe(collect($want[0])->sort()->values()->all());
        }
    }
    [$user] = polishPage('polish-form');
    $html = $this->actingAs($user)->get('/bio/polish-form/edit')->assertOk()->getContent();
    expect($html)->toContain("blockTab === 'look'")->toContain("blockTab === 'schedule'")->toContain("blockTab === 'advanced'")->toContain('backToAddBlock()');
});
