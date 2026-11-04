<?php

test('GET /b and /b/ redirect 301 to the app address (bio.hel.ink in production) preserving query parameters', function () {
    config(['app.url' => 'https://bio.hel.ink']);

    $this->get('/b')
        ->assertStatus(301)
        ->assertRedirect('https://bio.hel.ink/');

    $this->get('/b/')
        ->assertStatus(301)
        ->assertRedirect('https://bio.hel.ink/');

    $this->get('/b/?ref=promo&source=hop')
        ->assertStatus(301)
        ->assertRedirect('https://bio.hel.ink/?ref=promo&source=hop');
});
