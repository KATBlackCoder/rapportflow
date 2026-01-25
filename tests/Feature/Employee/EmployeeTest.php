<?php

use App\Enums\EmployeeStatus;
use App\Enums\Position;
use App\Models\Employee;
use App\Models\User;

test('employee can be created', function () {
    $employee = Employee::factory()->create([
        'employee_id' => 'EMP0001',
        'first_name' => 'Amadou',
        'last_name' => 'Traoré',
        'phone' => '12345678',
        'position' => Position::Employer,
        'status' => EmployeeStatus::Active,
    ]);

    expect($employee->employee_id)->toBe('EMP0001');
    expect($employee->first_name)->toBe('Amadou');
    expect($employee->last_name)->toBe('Traoré');
    expect($employee->phone)->toBe('12345678');
    expect($employee->position)->toBe(Position::Employer);
    expect($employee->status)->toBe(EmployeeStatus::Active);
});

test('employee has user relationship', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    expect($employee->user)->toBeInstanceOf(User::class);
    expect($employee->user->id)->toBe($user->id);
});

test('employee has manager relationship', function () {
    $manager = Employee::factory()->create();
    $employee = Employee::factory()->create(['manager_id' => $manager->id]);

    expect($employee->manager)->toBeInstanceOf(Employee::class);
    expect($employee->manager->id)->toBe($manager->id);
});

test('employee has subordinates relationship', function () {
    $manager = Employee::factory()->create();
    $subordinate1 = Employee::factory()->create(['manager_id' => $manager->id]);
    $subordinate2 = Employee::factory()->create(['manager_id' => $manager->id]);

    expect($manager->subordinates)->toHaveCount(2);
    expect($manager->subordinates->pluck('id')->toArray())->toContain($subordinate1->id, $subordinate2->id);
});

test('employee_id must be unique', function () {
    Employee::factory()->create(['employee_id' => 'EMP0001']);

    expect(fn () => Employee::factory()->create(['employee_id' => 'EMP0001']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('phone must be unique', function () {
    Employee::factory()->create(['phone' => '12345678']);

    expect(fn () => Employee::factory()->create(['phone' => '12345678']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('email must be unique', function () {
    Employee::factory()->create(['email' => 'test@example.com']);

    expect(fn () => Employee::factory()->create(['email' => 'test@example.com']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('display last name normalizes to uppercase without accents', function () {
    $employee = Employee::factory()->create(['last_name' => 'Traoré']);

    expect($employee->display_last_name)->toBe('TRAORE');
});

test('display last name handles special characters', function () {
    $employee = Employee::factory()->create(['last_name' => "N'Diaye"]);

    expect($employee->display_last_name)->toBe("N'DIAYE");
});

test('normalize last name for login returns lowercase without accents', function () {
    $employee = Employee::factory()->create(['last_name' => 'Traoré']);

    expect($employee->normalizeLastNameForLogin())->toBe('traore');
});

test('normalize last name for login handles special characters', function () {
    $employee = Employee::factory()->create(['last_name' => "N'Diaye"]);

    expect($employee->normalizeLastNameForLogin())->toBe("n'diaye");
});

test('employee can be created without user', function () {
    $employee = Employee::factory()->create(['user_id' => null]);

    expect($employee->user_id)->toBeNull();
    expect($employee->user)->toBeNull();
});

test('employee can be created without manager', function () {
    $employee = Employee::factory()->create(['manager_id' => null]);

    expect($employee->manager_id)->toBeNull();
    expect($employee->manager)->toBeNull();
});

test('employee factory states work correctly', function () {
    $inactive = Employee::factory()->inactive()->create();
    $suspended = Employee::factory()->suspended()->create();
    $terminated = Employee::factory()->terminated()->create();
    $manager = Employee::factory()->manager()->create();

    expect($inactive->status)->toBe(EmployeeStatus::Inactive);
    expect($suspended->status)->toBe(EmployeeStatus::Suspended);
    expect($terminated->status)->toBe(EmployeeStatus::Terminated);
    expect($manager->position)->toBe(Position::Manager);
});
