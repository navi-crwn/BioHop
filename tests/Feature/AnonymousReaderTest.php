<?php

use App\Http\Middleware\OptionalSession;
use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\Cache;

function cookieNames($response): array
{
    return array_map(fn ($c) => $c->getName(), $response->headers->getCookies());
}

beforeEach(fn () => Cache::flush());

test('a first-time reader of a plain page gets no session and no cookies', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'plainp', 'title' => 'Plain', 'is_published' => true]);
    $res = $this->get('/b/plainp')->assertOk()->assertSee('Plain');
    expect(cookieNames($res))->not->toContain('XSRF-TOKEN')->not->toContain(config('session.cookie'));
    // the shop address behaves the same once there is something to sell
    $page = BioPage::where('slug', 'plainp')->first();
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'product', 'title' => 'Tote', 'url' => 'https://shop.example/t', 'order' => 0, 'is_active' => true, 'settings' => ['price' => 5, 'currency' => 'USD']]);
    expect(cookieNames($this->get('/b/shop/plainp')->assertOk()))->not->toContain('XSRF-TOKEN');
});

test('pages that need a session still get one: forms, polls, passwords and age gates', function () {
    $user = User::factory()->create();
    $make = fn ($slug, $extra = []) => BioPage::create($extra + ['user_id' => $user->id, 'slug' => $slug, 'title' => $slug, 'is_published' => true]);
    $form = $make('withform');
    BioLink::create(['bio_page_id' => $form->id, 'type' => 'form', 'title' => 'Join', 'order' => 0, 'is_active' => true, 'settings' => ['ask_email' => true]]);
    $poll = $make('withpoll');
    BioLink::create(['bio_page_id' => $poll->id, 'type' => 'poll', 'title' => 'Vote', 'order' => 0, 'is_active' => true, 'settings' => ['options' => [['text' => 'A'], ['text' => 'B']]]]);
    $make('withpass', ['password_enabled' => true, 'password' => 'secret']);
    $make('withage', ['content_warning' => 'adult']);
    foreach (['withform', 'withpoll', 'withpass', 'withage'] as $slug) {
        expect(OptionalSession::pageNeedsNoSession($slug))->toBeFalse("{$slug} needs a session");
        expect(cookieNames($this->get("/b/{$slug}")))->toContain('XSRF-TOKEN');
    }
    // the sign-up form works end to end for a reader who had no session before
    $link = BioLink::where('bio_page_id', $form->id)->first();
    $this->get('/b/withform');
    $this->post(route('bio.public.form', $link->public_id), ['name' => 'Ann', 'email' => 'a@example.com', 'consent' => '1'])->assertRedirect();
    expect($link->leads()->count())->toBe(1);
});

test('a reader who already has a session cookie keeps using it, and saving a block makes the change count at once', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'flipp', 'title' => 'Flip', 'is_published' => true]);
    expect(OptionalSession::pageNeedsNoSession('flipp'))->toBeTrue();
    $res = $this->withCookie(config('session.cookie'), 'abc')->get('/b/flipp')->assertOk();
    expect(cookieNames($res))->toContain('XSRF-TOKEN');
    // adding a form through the editor forgets the remembered answer
    $this->actingAs($user)->postJson('/bio/flipp/links', ['type' => 'form', 'title' => 'Join', 'settings' => ['ask_email' => true]])->assertOk();
    expect(OptionalSession::pageNeedsNoSession('flipp'))->toBeFalse();
});
