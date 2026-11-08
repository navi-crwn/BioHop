<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

function faqPage(string $slug): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'FAQ page', 'is_published' => true]);

    return [$user, $page];
}

test('a FAQ block keeps its questions and answers and shows them on the page', function () {
    [$user, $page] = faqPage('faqone');
    $res = $this->actingAs($user)->postJson('/bio/faqone/links', [
        'type' => 'faq', 'title' => 'Good to know',
        'faq_items' => [
            ['question' => 'Do you ship abroad?', 'answer' => "Yes, to most countries.\nAsk for a quote."],
            ['question' => '  ', 'answer' => 'An answer without a question is dropped'],
            ['question' => 'Returns?', 'answer' => ''],
        ],
    ])->assertOk()->json();
    $block = BioLink::findOrFail($res['block']['id']);
    expect($block->faq_items)->toBe([
        ['question' => 'Do you ship abroad?', 'answer' => "Yes, to most countries.\nAsk for a quote."],
        ['question' => 'Returns?', 'answer' => ''],
    ]);
    $html = $this->get('/b/faqone')->assertOk()->getContent();
    expect($html)->toContain('Good to know')->toContain('Do you ship abroad?')->toContain('Ask for a quote.')->toContain('Returns?')
        ->toContain('aria-expanded')->not->toContain('An answer without a question');
});

test('editing a FAQ block replaces its questions, and a block left without questions shows nothing', function () {
    [$user, $page] = faqPage('faqtwo');
    $block = $page->links()->create(['type' => 'faq', 'title' => 'Help', 'order' => 0, 'is_active' => true,
        'faq_items' => [['question' => 'Old question', 'answer' => 'Old answer']]]);
    $this->actingAs($user)->putJson("/bio/faqtwo/links/{$block->id}", ['faq_items' => [['question' => 'New question', 'answer' => 'New answer']]])->assertOk();
    expect($block->fresh()->faq_items)->toBe([['question' => 'New question', 'answer' => 'New answer']]);
    $this->get('/b/faqtwo')->assertOk()->assertSee('New question')->assertDontSee('Old question');

    $this->putJson("/bio/faqtwo/links/{$block->id}", ['faq_items' => [['question' => '', 'answer' => 'x']]])->assertOk();
    expect($block->fresh()->faq_items)->toBeNull();
    $this->get('/b/faqtwo')->assertOk()->assertDontSee('New question');
});

test('the editor has a FAQ form with question and answer fields, and a type label instead of "Custom Link"', function () {
    [$user] = faqPage('faqform');
    $html = $this->actingAs($user)->get('/bio/faqform/edit')->assertOk()->getContent();
    expect($html)->toContain('data-faq-form')->toContain('addFaqItem()')->toContain('removeFaqItem(i)')
        ->toContain('placeholder="Question"')->toContain('placeholder="Answer"')->toContain('getDefaultBlockTitle(editingBlock?.type)');
});
