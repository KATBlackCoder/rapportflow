<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('first login page is displayed for users who have not changed password', function () {
    $user = User::factory()->create([
        'username' => 'test@12345678.org',
        'password_changed_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('first-login.show'));

    $response->assertOk();
});

test('user can keep default password', function () {
    $user = User::factory()->create([
        'username' => 'test@12345678.org',
        'password_changed_at' => null,
    ]);

    $response = $this->actingAs($user)->post(route('first-login.update'), [
        'action' => 'keep',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    expect($user->fresh()->hasChangedPassword())->toBeTrue();
});

test('user can change default password', function () {
    $user = User::factory()->create([
        'username' => 'test@12345678.org',
        'password_changed_at' => null,
    ]);

    $newPassword = 'NewSecurePassword123!';

    $response = $this->actingAs($user)->post(route('first-login.update'), [
        'action' => 'change',
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    expect($user->fresh()->hasChangedPassword())->toBeTrue();
    expect(Hash::check($newPassword, $user->fresh()->password))->toBeTrue();
});

test('password change requires confirmation', function () {
    $user = User::factory()->create([
        'username' => 'test@12345678.org',
        'password_changed_at' => null,
    ]);

    $response = $this->actingAs($user)->post(route('first-login.update'), [
        'action' => 'change',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'DifferentPassword123!',
    ]);

    $response->assertSessionHasErrors('password');
});

test('user is redirected to first login when password not changed', function () {
    $user = User::factory()->create([
        'username' => 'test@12345678.org',
        'password_changed_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('first-login.show'));
});
