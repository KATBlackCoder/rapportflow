<?php

use App\Enums\Position;
use App\Models\Employee;
use App\Models\User;

test('manager can access employees page', function () {
    $user = User::factory()->create([
        'password_changed_at' => now(),
    ]);

    Employee::factory()->create([
        'user_id' => $user->id,
        'position' => Position::Manager,
    ]);

    $response = $this->actingAs($user)->get(route('employees.index'));

    $response->assertOk();
});

test('chef superviseur can access employees page', function () {
    $user = User::factory()->create([
        'password_changed_at' => now(),
    ]);

    Employee::factory()->create([
        'user_id' => $user->id,
        'position' => Position::ChefSuperviseur,
    ]);

    $response = $this->actingAs($user)->get(route('employees.index'));

    $response->assertOk();
});

test('superviseur cannot access employees page', function () {
    $user = User::factory()->create([
        'password_changed_at' => now(),
    ]);

    Employee::factory()->create([
        'user_id' => $user->id,
        'position' => Position::Superviseur,
    ]);

    $response = $this->actingAs($user)->get(route('employees.index'));

    $response->assertForbidden();
});

test('employer cannot access employees page', function () {
    $user = User::factory()->create([
        'password_changed_at' => now(),
    ]);

    Employee::factory()->create([
        'user_id' => $user->id,
        'position' => Position::Employer,
    ]);

    $response = $this->actingAs($user)->get(route('employees.index'));

    $response->assertForbidden();
});

test('user without employee cannot access employees page', function () {
    $user = User::factory()->create([
        'password_changed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('employees.index'));

    $response->assertForbidden();
});

test('unauthenticated user cannot access employees page', function () {
    $response = $this->get(route('employees.index'));

    $response->assertRedirect(route('login'));
});
