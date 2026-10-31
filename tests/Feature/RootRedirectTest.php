<?php

test('GET /b and /b/ redirect 301 to bio.hel.ink preserving query parameters', function () {
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
