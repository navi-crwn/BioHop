<?php

use App\Models\User;
use App\Support\Reminders;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\DB;

/** The setup checklist on the dashboard is a reminder card: "Hide" keeps it away for seven days per account. */
function reminderMember(string $slug, array $page = []): array
{
    $user = User::factory()->create();
    $bio = BioPage::create($page + ['user_id' => $user->id, 'slug' => $slug, 'title' => 'Reminder page', 'is_published' => false]);

    return [$user, $bio];
}

test('the checklist is marked as a reminder card that posts to the dismiss address', function () {
    [$user] = reminderMember('remind-a');
    $this->actingAs($user)->get('/bio')->assertOk()
        ->assertSee('data-hop-remind="setup-checklist"', false)
        ->assertSee('data-hop-remind-url="'.route('reminders.dismiss').'"', false)
        ->assertSee('Set up your page');
});

test('hiding stores the time for this account and card only, and needs a sign-in and a known key', function () {
    [$user] = reminderMember('remind-b');
    [$other] = reminderMember('remind-c');
    $this->postJson('/reminders/dismiss', ['key' => 'setup-checklist'])->assertUnauthorized();
    $this->actingAs($user)->postJson('/reminders/dismiss', ['key' => 'made-up'])->assertStatus(422);
    $this->postJson('/reminders/dismiss', [])->assertStatus(422);
    expect(DB::table('bio_reminders')->count())->toBe(0);

    $res = $this->postJson('/reminders/dismiss', ['key' => 'setup-checklist'])->assertOk()->json();
    expect($res['ok'])->toBeTrue()->and($res['shows_again_at'])->toStartWith(now()->addDays(7)->toDateString());
    // A second press updates the same row.
    $this->postJson('/reminders/dismiss', ['key' => 'setup-checklist'])->assertOk();
    expect(DB::table('bio_reminders')->where('user_id', $user->id)->count())->toBe(1)
        ->and(DB::table('bio_reminders')->where('user_id', $other->id)->count())->toBe(0);

    $this->get('/bio')->assertOk()->assertDontSee('data-hop-remind', false)->assertDontSee('Set up your page');
    $this->actingAs($other)->get('/bio')->assertOk()->assertSee('data-hop-remind="setup-checklist"', false);
});

test('a hidden card comes back after seven days while the task is not done', function () {
    [$user] = reminderMember('remind-d');
    $this->actingAs($user)->postJson('/reminders/dismiss', ['key' => 'setup-checklist'])->assertOk();

    $this->travel(6)->days();
    $this->get('/bio')->assertOk()->assertDontSee('data-hop-remind', false);
    $this->travel(2)->days();
    $this->get('/bio')->assertOk()->assertSee('data-hop-remind="setup-checklist"', false)->assertSee('Set up your page');

    // Hiding it again starts another seven days.
    $this->postJson('/reminders/dismiss', ['key' => 'setup-checklist'])->assertOk();
    $this->get('/bio')->assertOk()->assertDontSee('data-hop-remind', false);
});

test('a hidden card stays away for good once every step is done, and a finished one that was never hidden still shows', function () {
    $done = ['avatar_url' => 'avatars/me.webp', 'bio' => 'Hi', 'theme' => 'ocean', 'is_published' => true,
        'social_links' => [['platform' => 'x', 'value' => 'helena', 'enabled' => true]]];
    [$user, $page] = reminderMember('remind-e', $done);
    $page->links()->create(['type' => 'link', 'title' => 'Site', 'url' => 'https://example.com', 'order' => 0, 'is_active' => true]);
    $this->actingAs($user)->get('/bio')->assertOk()->assertSee('Your page is set up')->assertSee('6 of 6 complete');

    $this->postJson('/reminders/dismiss', ['key' => 'setup-checklist'])->assertOk();
    $this->travel(30)->days();
    $this->get('/bio')->assertOk()->assertDontSee('Your page is set up')->assertDontSee('data-hop-remind', false);
    expect(Reminders::hidden($user->id, 'setup-checklist', true))->toBeTrue()
        ->and(Reminders::hidden($user->id, 'setup-checklist', false))->toBeFalse();
});
