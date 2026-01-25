<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registration creates user and employee with generated username and password', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'Amadou',
        'last_name' => 'Traoré',
        'phone' => '12345678',
        'position' => 'employer',
        'department' => 'IT',
        'email' => 'amadou@example.com',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('username', 'traore@12345678.org')->first();
    expect($user)->not->toBeNull();
    expect($user->email)->toBe('amadou@example.com');
    expect($user->password_changed_at)->toBeNull();
    expect(Hash::check('ML12345678', $user->password))->toBeTrue();

    $employee = Employee::where('user_id', $user->id)->first();
    expect($employee)->not->toBeNull();
    expect($employee->first_name)->toBe('Amadou');
    expect($employee->last_name)->toBe('Traoré');
    expect($employee->phone)->toBe('12345678');
    expect($employee->display_last_name)->toBe('TRAORE');
});

test('registration normalizes last name for username', function () {
    $this->post(route('register.store'), [
        'first_name' => 'Jean',
        'last_name' => "N'Diaye",
        'phone' => '87654321',
        'position' => 'manager',
    ]);

    $user = User::where('username', "n'diaye@87654321.org")->first();
    expect($user)->not->toBeNull();
});

test('registration handles username collisions', function () {
    // Create first user
    $this->post(route('register.store'), [
        'first_name' => 'Amadou',
        'last_name' => 'Traoré',
        'phone' => '12345678',
        'position' => 'employer',
    ]);

    // Create second user with same last name and phone
    $this->post(route('register.store'), [
        'first_name' => 'Moussa',
        'last_name' => 'Traoré',
        'phone' => '12345678',
        'position' => 'employer',
    ]);

    $user1 = User::where('username', 'traore@12345678.org')->first();
    $user2 = User::where('username', 'traore1@12345678.org')->first();

    expect($user1)->not->toBeNull();
    expect($user2)->not->toBeNull();
});

test('registration validates required fields', function () {
    $response = $this->post(route('register.store'), []);

    $response->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'position']);
});

test('registration validates phone format', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'Amadou',
        'last_name' => 'Traoré',
        'phone' => '123', // Invalid: must be 8 digits
        'position' => 'employer',
    ]);

    $response->assertSessionHasErrors('phone');
});

test('registration validates unique phone', function () {
    $this->post(route('register.store'), [
        'first_name' => 'Amadou',
        'last_name' => 'Traoré',
        'phone' => '12345678',
        'position' => 'employer',
    ]);

    $response = $this->post(route('register.store'), [
        'first_name' => 'Moussa',
        'last_name' => 'Diallo',
        'phone' => '12345678', // Same phone
        'position' => 'employer',
    ]);

    $response->assertSessionHasErrors('phone');
});
