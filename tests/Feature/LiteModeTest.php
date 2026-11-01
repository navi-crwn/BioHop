<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;

test('the public page carries the lite-mode rules, and a Save-Data visitor gets the lite class from the server', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'litep', 'title' => 'Lite', 'is_published' => true, 'background_animation' => 'snow']);
    $normal = $this->get('/b/litep')->assertOk()->getContent();
    expect($normal)->toContain('.lite #bg-animation { display: none !important; }')->toContain('navigator.deviceMemory')->toContain('saveData')->not->toContain('class="lite"');
    $lite = $this->withHeader('Save-Data', 'on')->get('/b/litep')->assertOk()->getContent();
    expect($lite)->toContain('class="lite"');
});
