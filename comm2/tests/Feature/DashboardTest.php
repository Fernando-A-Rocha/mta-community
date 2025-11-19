<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/platform')->assertRedirect('/login');
});

test('authenticated users can visit the platform', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/platform')->assertStatus(200);
});
