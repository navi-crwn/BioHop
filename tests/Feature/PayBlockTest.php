<?php

use App\Models\User;
use Hop\Bio\Contracts\UrlSafety;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\PayBlock;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

/** Invented data only: none of these is a real account, number or address. */
function payValid(): array
{
    $sys = '11111111111111111111111111111111';

    return [
        'paypal' => ['user' => 'jdoe', 'currency' => 'USD'],
        'cashapp' => ['cashtag' => '$jdoe'],
        'venmo' => ['user' => 'jane-doe'],
        'solana' => ['recipient' => $sys, 'label' => 'Cafe Ana'],
        'upi' => ['name' => 'Warung Sri', 'vpa' => 'shop@okbank'],
        'epc' => ['name' => 'Taman Baca Fund', 'iban' => 'DE89 3704 0044 0532 0130 00'],
        'promptpay' => ['name' => 'Nama Contoh', 'phone' => '060 000 0000'],
        'vietqr' => ['name' => 'Nguyen Van A', 'bin' => '970436', 'account' => '0123456789'],
    ];
}

function payPage(string $slug): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Pay page', 'is_published' => true]);

    return [$user, $page];
}

beforeEach(function () {
    config(['services.turnstile.secret' => null, 'services.turnstile.site_key' => null]);
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('the format list is the module\'s list: every format has a valid stored shape here', function () {
    expect(array_keys(PayBlock::formats()))->toEqual(array_keys(payValid()));
    foreach (payValid() as $id => $fields) {
        [$clean, $errors] = PayBlock::clean(['format' => $id, 'fields' => $fields, 'amount' => '12.5', 'note' => 'Thanks']);
        expect($errors)->toBe([], $id)->and($clean['format'])->toBe($id);
        expect($clean['amount'])->toBe('12.5');
        // formats without a note field keep none
        expect($clean['note'])->toBe(PayBlock::formats()[$id]['note'] ? 'Thanks' : '');
    }
});

test('the shape check refuses what cannot be a payment block, per format', function () {
    foreach (payValid() as $id => $fields) {
        $def = PayBlock::formats()[$id];
        $required = collect($def['fields'])->first(fn ($f) => empty($f['optional']) && ($f['kind'] ?? '') !== 'currency');
        // a required field left empty
        [, $errors] = PayBlock::clean(['format' => $id, 'fields' => array_merge($fields, [$required['key'] => ' '])]);
        expect($errors)->toHaveKey('settings.pay.fields.'.$required['key']);
        // too long
        [, $errors] = PayBlock::clean(['format' => $id, 'fields' => array_merge($fields, [$required['key'] => str_repeat('a', $required['max'] + 1)])]);
        expect($errors)->toHaveKey('settings.pay.fields.'.$required['key']);
        // a zero-width space or a right-to-left override
        [, $errors] = PayBlock::clean(['format' => $id, 'fields' => array_merge($fields, [$required['key'] => "a\u{200B}b"])]);
        expect($errors)->toHaveKey('settings.pay.fields.'.$required['key']);
    }
    expect(PayBlock::clean(['format' => 'qris', 'fields' => []])[1])->toHaveKey('settings.pay.format');
    expect(PayBlock::clean(null)[1])->toHaveKey('settings.pay.format');
    expect(PayBlock::clean(['format' => 'paypal', 'fields' => ['user' => 'jdoe', 'currency' => 'XXX']])[1])->toHaveKey('settings.pay.fields.currency');
    expect(PayBlock::clean(['format' => 'paypal', 'fields' => payValid()['paypal'], 'amount' => '1e3'])[1])->toHaveKey('settings.pay.amount');
    expect(PayBlock::clean(['format' => 'venmo', 'fields' => payValid()['venmo'], 'note' => str_repeat('x', 141)])[1])->toHaveKey('settings.pay.note');
    // unknown fields are dropped, a fixed amount switches "visitors type it" off
    [$clean] = PayBlock::clean(['format' => 'cashapp', 'fields' => ['cashtag' => 'jdoe', 'evil' => 'x'], 'amount' => '5', 'open_amount' => true]);
    expect($clean['fields'])->toBe(['cashtag' => 'jdoe'])->and($clean['open_amount'])->toBeFalse();
});

test('the editor saves a payment block, refuses a bad one, and keeps the settings only on payment blocks', function () {
    [$user, $page] = payPage('payedit');
    $ok = ['type' => 'pay', 'title' => 'Tip me', 'settings' => ['pay' => ['format' => 'epc', 'fields' => payValid()['epc'], 'amount' => '12.34', 'note' => 'Donation', 'open_amount' => false]]];
    $res = $this->actingAs($user)->postJson('/bio/payedit/links', $ok)->assertOk();
    $link = BioLink::findOrFail($res->json('block.id'));
    expect($link->settings['pay']['fields']['iban'])->toBe('DE89 3704 0044 0532 0130 00')->and($link->settings['pay']['note'])->toBe('Donation');

    $bad = $ok;
    $bad['settings']['pay']['fields']['iban'] = '';
    $this->postJson('/bio/payedit/links', $bad)->assertStatus(422)->assertJsonValidationErrors('settings.pay.fields.iban');
    $this->postJson('/bio/payedit/links', ['type' => 'pay', 'title' => 'x'])->assertStatus(422)->assertJsonValidationErrors('settings.pay.format');
    $this->postJson('/bio/payedit/links', ['type' => 'pay', 'settings' => ['pay' => ['format' => 'qris', 'fields' => []]]])->assertStatus(422);
    expect(BioLink::where('bio_page_id', $page->id)->count())->toBe(1);

    // update: a bad change is refused and the stored block stays as it was
    $this->putJson('/bio/payedit/links/'.$link->id, ['settings' => ['pay' => ['format' => 'epc', 'fields' => ['name' => '', 'iban' => 'x']]]])->assertStatus(422);
    expect($link->fresh()->settings['pay']['fields']['name'])->toBe('Taman Baca Fund');
    $this->putJson('/bio/payedit/links/'.$link->id, ['title' => 'New title'])->assertOk();
    expect($link->fresh()->settings['pay']['format'])->toBe('epc');

    // payment settings sent for any other block type are not stored
    $res = $this->postJson('/bio/payedit/links', ['type' => 'text', 'title' => 'T', 'content' => 'hi', 'settings' => ['pay' => $ok['settings']['pay']]])->assertOk();
    expect(BioLink::findOrFail($res->json('block.id'))->settings['pay'] ?? null)->toBeNull();
});

test('the payee name and note go through the same content check as other block text', function () {
    [$user] = payPage('paysafe');
    $fake = Mockery::mock(UrlSafety::class);
    $fake->shouldReceive('rejectionReason')->andReturn(null);
    $fake->shouldReceive('assess')->withArgs(fn ($url, $text) => str_contains((string) $text, 'Totally Blocked Name'))->once()->andReturn(['signals' => ['phishing'], 'score' => 90]);
    $fake->shouldReceive('assess')->andReturn(['signals' => [], 'score' => 0]);
    app()->instance(UrlSafety::class, $fake);
    config(['safety.block_categories' => ['phishing']]);

    $pay = fn (string $name) => ['type' => 'pay', 'title' => 'Pay', 'settings' => ['pay' => ['format' => 'upi', 'fields' => ['name' => $name, 'vpa' => 'shop@okbank']]]];
    $this->actingAs($user)->postJson('/bio/paysafe/links', $pay('Totally Blocked Name'))->assertStatus(422)->assertJsonValidationErrors('title');
    $this->postJson('/bio/paysafe/links', $pay('Warung Sri'))->assertOk();
});

test('the public page prints the payee and every detail as text, so it reads without scripts', function () {
    [, $page] = payPage('paypub');
    $pay = ['format' => 'epc', 'fields' => payValid()['epc'], 'amount' => '12.34', 'note' => 'Donation', 'open_amount' => false];
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'pay', 'title' => 'Support us', 'order' => 0, 'is_active' => true, 'settings' => ['pay' => $pay]]);

    $html = $this->get('/b/paypub')->assertOk()->getContent();
    expect($html)->toContain('class="bio-pay ')->toContain('Support us')
        ->toContain('Taman Baca Fund')->toContain('EPC SEPA')->toContain('DE89 3704 0044 0532 0130 00')
        ->toContain('12.34 EUR')->toContain('Donation')
        // the QR card and the amount form stay hidden until the script runs; a fixed amount has no form
        ->toContain('data-pay-qr hidden')->not->toContain('data-pay-open')
        ->toContain('does not process payments')->toContain('check the name before you confirm')
        // nothing is loaded from another host for it
        ->not->toContain('pay.hel.ink');
});

test('a block with an open amount shows the amount form only for scripts, and says what to do without them', function () {
    [, $page] = payPage('payopen');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'pay', 'title' => 'Tip jar', 'order' => 0, 'is_active' => true,
        'settings' => ['pay' => ['format' => 'paypal', 'fields' => payValid()['paypal'], 'amount' => '', 'note' => '', 'open_amount' => true]]]);
    $html = $this->get('/b/payopen')->assertOk()->getContent();
    expect($html)->toContain('data-pay-open hidden')->toContain('<noscript>')->toContain('Enter the amount in your own bank or wallet app.')
        ->toContain('jdoe')->not->toContain('Amount</dt>');
});

test('stored payment details are escaped on the page, in the text and in the data attribute', function () {
    [, $page] = payPage('payxss');
    $evil = '<script>alert(1)</script>"\'><img src=x onerror=alert(2)>';
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'pay', 'title' => $evil, 'order' => 0, 'is_active' => true,
        'settings' => ['pay' => ['format' => 'upi', 'fields' => ['name' => $evil, 'vpa' => 'shop@okbank'], 'amount' => '5', 'note' => $evil]]]);
    $html = $this->get('/b/payxss')->assertOk()->getContent();
    expect($html)->not->toContain('<script>alert(1)')->not->toContain('<img src=x')
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});

test('a payment block with unusable stored data is not shown, and the page without one is unchanged', function () {
    [, $page] = payPage('paynone');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'My site', 'url' => 'https://example.com', 'order' => 0, 'is_active' => true]);
    $before = $this->get('/b/paynone')->assertOk()->getContent();
    expect($before)->not->toContain('data-pay')->not->toContain('bio-pay');

    BioLink::create(['bio_page_id' => $page->id, 'type' => 'pay', 'title' => 'Broken', 'order' => 1, 'is_active' => true, 'settings' => ['pay' => ['format' => 'gone', 'fields' => []]]]);
    $after = $this->get('/b/paynone')->assertOk()->getContent();
    expect($after)->not->toContain('data-pay')->not->toContain('Broken');
});

test('the payment block is no click target, and it is listed with its own name', function () {
    expect(BioLink::INERT_TYPES)->toContain('pay')->and(BioLink::TYPE_NAMES['pay'])->toBe('Payment');
    $link = new BioLink(['type' => 'pay', 'title' => '', 'settings' => ['pay' => ['format' => 'upi', 'fields' => payValid()['upi']]]]);
    expect($link->displayTitle())->toBe('Warung Sri')->and($link->displaySummary())->toContain('UPI');
});

test('every new string is translated in all nine languages', function () {
    $editor = (string) file_get_contents(base_path('packages/bio/resources/views/editor/index.blade.php'));
    $start = strpos($editor, "editingBlock?.type === 'pay'");
    $form = substr($editor, $start, strpos($editor, "editingBlock?.type === 'goal'") - $start);
    $show = (string) file_get_contents(base_path('packages/bio/resources/views/show.blade.php'));
    $start = strpos($show, "\$link->type === 'pay'");
    $public = substr($show, $start, strpos($show, "\$link->type === 'goal'") - $start);

    $keys = [];
    foreach ([$form, $public] as $src) {
        preg_match_all("/__\\('((?:[^'\\\\]|\\\\.)*)'\\)/", $src, $m);
        $keys = array_merge($keys, array_map('stripslashes', $m[1]));
    }
    $keys = array_merge($keys, ['Receive payment', 'A QR code for your bank or wallet', 'The QR code appears on your public page.']);
    $keys = array_merge($keys, array_values(array_unique(PayBlock::messages())), array_keys(PayBlock::labels()));
    expect(count($keys))->toBeGreaterThan(50);

    foreach (['en', 'zh-CN', 'de', 'id', 'ja', 'pt-BR', 'es', 'fr', 'ru'] as $lang) {
        $d = json_decode((string) file_get_contents(base_path("lang/$lang.json")), true);
        foreach (array_unique($keys) as $k) {
            expect($d)->toHaveKey($k);
            expect($d[$k])->not->toBe('');
        }
    }
    expect(file_exists(base_path('lang/ar.json')))->toBeFalse();
});

test('the vendored hop-pay and the format list pass their JS tests', function () {
    $node = trim((string) shell_exec('command -v node 2>/dev/null'));
    if ($node === '') {
        $this->markTestSkipped('node is not installed');
    }
    exec(escapeshellarg($node).' --test '.escapeshellarg(base_path('tests/js/pay.test.mjs')).' 2>&1', $out, $code);
    expect($code)->toBe(0, implode("\n", $out));
    exec(escapeshellarg($node).' '.escapeshellarg(base_path('scripts/vendor-hop-pay.mjs')).' --check 2>&1', $out2, $code2);
    expect($code2)->toBe(0, implode("\n", $out2));
});

test('a hand-made request cannot store details the module would reject', function () {
    $sys = '11111111111111111111111111111111';
    $bad = [
        ['paypal', ['user' => 'j doe', 'currency' => 'USD'], 'user'],
        ['paypal', ['user' => str_repeat('a', 21), 'currency' => 'USD'], 'user'],
        ['cashapp', ['cashtag' => '12345'], 'cashtag'],
        ['cashapp', ['cashtag' => 'a/b'], 'cashtag'],
        ['venmo', ['user' => 'ab'], 'user'],
        ['solana', ['recipient' => $sys.'x', 'label' => 'Ana'], 'recipient'],
        ['solana', ['recipient' => '0OIl'.$sys, 'label' => 'Ana'], 'recipient'],
        ['solana', ['recipient' => $sys, 'label' => 'Ana', 'splToken' => 'abc'], 'splToken'],
        ['upi', ['name' => 'Ana', 'vpa' => 'shop'], 'vpa'],
        ['epc', ['name' => 'Ana', 'iban' => 'DE89 3704 0044 0532 0130 01'], 'iban'],
        ['epc', ['name' => 'Ana', 'iban' => 'XX00'], 'iban'],
        ['epc', ['name' => 'Ana', 'iban' => 'DE89 3704 0044 0532 0130 00', 'bic' => 'XX'], 'bic'],
        ['promptpay', ['name' => 'Ana', 'phone' => '12345'], 'phone'],
        ['vietqr', ['name' => 'Ana', 'bin' => '12', 'account' => '123'], 'bin'],
        ['vietqr', ['name' => 'Ana', 'bin' => '970436', 'account' => 'abc'], 'account'],
    ];
    foreach ($bad as [$id, $fields, $key]) {
        [, $errors] = PayBlock::clean(['format' => $id, 'fields' => $fields]);
        expect(isset($errors['settings.pay.fields.'.$key]))->toBeTrue($id.'.'.$key);
    }
    // the shapes the module accepts still pass, including the forms it normalises
    foreach ([
        ['paypal', ['user' => 'https://paypal.me/jdoe', 'currency' => 'EUR']],
        ['cashapp', ['cashtag' => '$jdoe']],
        ['venmo', ['user' => '@jane-doe']],
        ['epc', ['name' => 'Ana', 'iban' => 'gb82 west 1234 5698 7654 32', 'bic' => 'dabadkkk']],
        ['promptpay', ['name' => 'Ana', 'phone' => '+66 60 000 0000']],
        ['vietqr', ['name' => 'Ana', 'bin' => '970436', 'account' => '0123-4567 89']],
    ] as [$id, $fields]) {
        expect(PayBlock::clean(['format' => $id, 'fields' => $fields])[1])->toBe([], $id);
    }

    [$user] = payPage('payhand');
    $res = $this->actingAs($user)->postJson('/bio/payhand/links', ['type' => 'pay', 'title' => 'x', 'settings' => ['pay' => ['format' => 'epc', 'fields' => ['name' => 'Ana', 'iban' => 'DE89 3704 0044 0532 0130 01']]]]);
    $res->assertStatus(422)->assertJsonValidationErrors('settings.pay.fields.iban');
    expect(BioLink::count())->toBe(0);
});

test('a stored block the shape check rejects is not printed on the page', function () {
    [, $page] = payPage('paybad');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'pay', 'title' => 'Old', 'order' => 0, 'is_active' => true,
        'settings' => ['pay' => ['format' => 'epc', 'fields' => ['name' => 'Ana', 'iban' => 'DE89 3704 0044 0532 0130 01']]]]);
    $this->get('/b/paybad')->assertOk()->assertDontSee('DE89 3704')->assertDontSee('data-pay-details', false);
});

test('updating a payment block with other settings keeps the stored payment details', function () {
    [$user] = payPage('payupd');
    $id = $this->actingAs($user)->postJson('/bio/payupd/links', ['type' => 'pay', 'title' => 'Tip', 'settings' => ['pay' => ['format' => 'cashapp', 'fields' => ['cashtag' => 'jdoe']]]])->assertOk()->json('block.id');
    $this->putJson('/bio/payupd/links/'.$id, ['settings' => ['text' => 'unrelated']])->assertOk();
    expect(BioLink::find($id)->settings['pay']['fields']['cashtag'])->toBe('jdoe');
    // a change that does carry details replaces them
    $this->putJson('/bio/payupd/links/'.$id, ['settings' => ['pay' => ['format' => 'cashapp', 'fields' => ['cashtag' => 'other1']]]])->assertOk();
    expect(BioLink::find($id)->settings['pay']['fields']['cashtag'])->toBe('other1');
});
