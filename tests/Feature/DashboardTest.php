<?php

use App\Enums\Position;
use App\Models\Employee;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create([
        'password_changed_at' => now(),
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard returns stats and role-based props for user with employee', function () {
    $user = User::factory()->create([
        'password_changed_at' => now(),
    ]);
    Employee::factory()->create([
        'user_id' => $user->id,
        'position' => Position::Employer,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard/Index')
        ->has('stats')
        ->has('recentReports')
        ->has('pendingCorrections')
        ->has('lastReport')
        ->has('canAccessQuestionnaires')
        ->has('canAccessEmployees')
        ->has('canExportReports')
    );
});