<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Enums\Position;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = fake()->numerify('########');

        return [
            'employee_id' => 'EMP'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => $phone,
            'position' => fake()->randomElement(Position::cases()),
            'department' => fake()->optional()->randomElement(['IT', 'RH', 'Finance', 'Marketing', 'Ventes', 'Production']),
            'salary' => fake()->optional()->randomFloat(2, 50000, 200000),
            'hire_date' => fake()->optional()->dateTimeBetween('-5 years', 'now'),
            'status' => EmployeeStatus::Active,
        ];
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeStatus::Inactive,
        ]);
    }

    /**
     * Indicate that the employee is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeStatus::Suspended,
        ]);
    }

    /**
     * Indicate that the employee is terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeStatus::Terminated,
        ]);
    }

    /**
     * Indicate that the employee is a manager.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => Position::Manager,
        ]);
    }
}
