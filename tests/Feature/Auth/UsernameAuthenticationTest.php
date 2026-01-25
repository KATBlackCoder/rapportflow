<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user can login with username instead of email', function () {
    $user = User::factory()->create([
        'username' => 'traore@12345678.org',
        'password' => Hash::make('ML12345678'),
        'password_changed_at' => now(),
    ]);

    $response = $this->post(route('login.store'), [
        'username' => 'traore@12345678.org',
        'password' => 'ML12345678',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('user cannot login with email', function () {
    $user = User::factory()->create([
        'username' => 'traore@12345678.org',
        'email' => 'test@example.com',
        'password' => Hash::make('ML12345678'),
        'password_changed_at' => now(),
    ]);

    $response = $this->post(route('login.store'), [
        'username' => 'test@example.com',
        'password' => 'ML12345678',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('username');
});

test('login fails with incorrect username', function () {
    $response = $this->post(route('login.store'), [
        'username' => 'nonexistent@12345678.org',
        'password' => 'ML12345678',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('username');
});

test('login fails with incorrect password', function () {
    $user = User::factory()->create([
        'username' => 'traore@12345678.org',
        'password' => Hash::make('ML12345678'),
        'password_changed_at' => now(),
    ]);

    $response = $this->post(route('login.store'), [
        'username' => 'traore@12345678.org',
        'password' => 'WrongPassword',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('username');
});
