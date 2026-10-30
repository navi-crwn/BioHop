<?php

use App\Models\User;

test('/admin sends an admin to the control center when HOP_CONTROL_URL is set', function (string $path) {
    config(['hop.control_url' => 'https://imperium.example.test']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get($path)->assertRedirect('https://imperium.example.test');
})->with(['/admin', '/admin/bio', '/admin/bio/monitor', '/admin/bio/pages/123']);

test('/admin explains where the admin panel went when no control center is configured', function () {
    config(['hop.control_url' => null, 'hop.control_name' => 'Imperium']);
    $admin = User::factory()->owner()->create();

    $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('The admin panel moved')->assertSee('Imperium')->assertSee('HOP_CONTROL_URL');
    $this->actingAs($admin)->get('/admin/bio/monitor')->assertOk()->assertSee('The admin panel moved');
});

test('/admin is forbidden for a normal user', function () {
    config(['hop.control_url' => 'https://imperium.example.test']);
    $member = User::factory()->create();

    $this->actingAs($member)->get('/admin')->assertForbidden();
    $this->actingAs($member)->get('/admin/bio')->assertForbidden();
});

test('/admin asks a guest to sign in', function () {
    config(['hop.control_url' => 'https://imperium.example.test']);

    $this->get('/admin')->assertRedirect('/auth/sso?next=%2Fadmin');
});

test('admins see the control center in the sidebar when HOP_CONTROL_URL is configured, members do not', function () {
    config(['hop.control_url' => 'https://imperium.example.test', 'hop.control_name' => 'Imperium']);
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($admin)->get(route('bio.index'))->assertOk()
        ->assertSee('https://imperium.example.test')->assertSee('Imperium')->assertDontSee('Admin: bio pages');
    $this->actingAs($member)->get(route('bio.index'))->assertOk()->assertDontSee('imperium.example.test');

    config(['hop.control_url' => null]);
    $this->actingAs($admin)->get(route('bio.index'))->assertOk()->assertDontSee('imperium.example.test')->assertDontSee('Admin: bio pages');
});
