<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioPollVote;

function pollPage(string $slug): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Polls', 'is_published' => true]);
    $poll = BioLink::create(['bio_page_id' => $page->id, 'type' => 'poll', 'title' => 'Next video?', 'order' => 0, 'is_active' => true, 'settings' => ['options' => [['text' => 'Studio tour'], ['text' => 'Drawing tips'], ['text' => ' ']]]]);

    return [$user, $page, $poll];
}

test('a poll shows its options, then the results after the visitor votes, once per device', function () {
    [, $page, $poll] = pollPage('pollp');
    expect($poll->pollOptions())->toBe(['Studio tour', 'Drawing tips']);
    $html = $this->get('/b/pollp')->assertOk()->getContent();
    expect($html)->toContain('Next video?')->toContain('name="option" value="0"')->toContain('Drawing tips')->not->toContain('role="progressbar"');
    $this->post(route('bio.public.poll', $poll->public_id), ['option' => 1])->assertRedirect(route('bio.public.show', $page->slug).'#poll-'.$poll->public_id);
    $this->post(route('bio.public.poll', $poll->public_id), ['option' => 0]);
    expect(BioPollVote::count())->toBe(1)->and($poll->pollResults()['counts'])->toBe([0, 1]);
    $html = $this->get('/b/pollp')->assertOk()->getContent();
    expect($html)->toContain('class="bio-poll-res is-mine"')->toContain('1 vote')->not->toContain('name="option" value="0"');
    // a different device (browser) can still vote
    $this->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Version/17.0 Safari/605.1.15')->post(route('bio.public.poll', $poll->public_id), ['option' => 0]);
    expect($poll->pollResults()['total'])->toBe(2)->and($poll->pollResults()['counts'])->toBe([1, 1]);
});

test('votes outside the options and polls with fewer than two options are ignored', function () {
    [, , $poll] = pollPage('pollq');
    $this->post(route('bio.public.poll', $poll->public_id), ['option' => 5]);
    $this->post(route('bio.public.poll', $poll->public_id), ['option' => 'x']);
    expect(BioPollVote::count())->toBe(0);
    $poll->update(['settings' => ['options' => [['text' => 'Only one']]]]);
    $this->post(route('bio.public.poll', $poll->public_id), ['option' => 0])->assertNotFound();
    $this->get('/b/pollq')->assertOk()->assertDontSee('Next video?');
});

test('the owner sees the counts, can reset them, and the options are validated', function () {
    [$user, $page, $poll] = pollPage('pollr');
    $this->post(route('bio.public.poll', $poll->public_id), ['option' => 0]);
    $this->actingAs($user)->getJson("/bio/pollr/links/{$poll->id}/poll")->assertOk()->assertJson(['total' => 1, 'counts' => [1, 0]]);
    $this->putJson("/bio/pollr/links/{$poll->id}", ['settings' => ['options' => array_fill(0, 7, ['text' => 'x'])]])->assertStatus(422);
    $this->putJson("/bio/pollr/links/{$poll->id}", ['settings' => ['options' => [['text' => str_repeat('a', 81)]]]])->assertStatus(422);
    $this->deleteJson("/bio/pollr/links/{$poll->id}/poll")->assertOk()->assertJson(['deleted' => 1]);
    expect(BioPollVote::count())->toBe(0);
    $other = User::factory()->create();
    $this->actingAs($other)->getJson("/bio/pollr/links/{$poll->id}/poll")->assertForbidden();
});
