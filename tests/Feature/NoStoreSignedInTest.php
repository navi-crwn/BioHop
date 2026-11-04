<?php

use App\Models\User;

it('does not let the browser keep a signed-in BioHop page (Back after logout)', function () {
    $user = User::factory()->create();
    $res = $this->actingAs($user)->get('/settings');
    $res->assertOk();
    expect($res->headers->get('Cache-Control'))->toContain('no-store');
});

it('sends a suspended member to the hub notice, like LinkHop does', function () {
    $user = User::factory()->create();
    $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
    $this->actingAs($user->fresh())->get('/bio')->assertRedirect(rtrim(config('hop.hub_url'), '/').'/suspended');
    $this->assertGuest();
});

it('keeps a JSON 403 for a suspended member calling the API', function () {
    $user = User::factory()->create();
    $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
    $this->actingAs($user->fresh())->getJson('/bio')->assertForbidden();
});
