<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;

function adminPage(User $owner, string $slug = 'adminpage'): BioPage
{
    return BioPage::create(['user_id' => $owner->id, 'slug' => $slug, 'title' => 'Page', 'theme' => 'default', 'is_published' => true]);
}

test('members cannot open the admin pages', function () {
    $this->get('/admin/bio')->assertRedirect('/auth/sso?next=%2Fadmin%2Fbio');
    $this->actingAs(User::factory()->create())->get('/admin/bio')->assertForbidden();
});

test('hop admins, app admins and the owner can list, inspect and unpublish bio pages', function () {
    $page = adminPage(User::factory()->create());
    foreach ([User::factory()->admin()->create(), User::factory()->admin('biohop')->create(), User::factory()->owner()->create()] as $admin) {
        $this->actingAs($admin)->get('/admin/bio')->assertOk()->assertSee($page->slug);
        $this->actingAs($admin)->get('/admin/bio/'.$page->slug)->assertOk();
        $this->actingAs($admin)->get('/admin/bio/monitor')->assertOk();
    }
    $this->actingAs(User::factory()->admin('linkhop')->create())->get('/admin/bio')->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->post('/admin/bio/'.$page->slug.'/toggle')->assertRedirect();
    expect($page->fresh()->is_published)->toBeFalse();
});

test('SUPERADMIN_EMAIL names the owner even without a role row', function () {
    config(['auth.superadmin_email' => 'boss@hel.ink']);
    $boss = User::factory()->create(['email' => 'boss@hel.ink']);
    expect($boss->isAdmin())->toBeTrue();
    $this->actingAs($boss)->get('/admin/bio')->assertOk();
});
