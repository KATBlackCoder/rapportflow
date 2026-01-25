<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Enums\Position;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user with username
        User::factory()->create([
            'name' => 'Test User',
            'username' => 'test@12345678.org',
            'email' => 'test@example.com',
            'password' => Hash::make('ML12345678'),
            'password_changed_at' => now(),
        ]);

        // Create employees with associated users
        $this->createEmployeeWithUser([
            'first_name' => 'Amadou',
            'last_name' => 'Traoré',
            'phone' => '12345678',
            'position' => Position::Manager,
            'department' => 'IT',
            'email' => 'amadou@example.com',
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Fatoumata',
            'last_name' => 'Diallo',
            'phone' => '87654321',
            'position' => Position::ChefSuperviseur,
            'department' => 'RH',
            'email' => 'fatoumata@example.com',
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Moussa',
            'last_name' => 'Cissé',
            'phone' => '11223344',
            'position' => Position::Superviseur,
            'department' => 'Production',
            'email' => 'moussa@example.com',
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Aissata',
            'last_name' => 'Sangaré',
            'phone' => '55667788',
            'position' => Position::Superviseur,
            'department' => 'Ventes',
            'email' => 'aissata@example.com',
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Ibrahim',
            'last_name' => 'Konaté',
            'phone' => '22334455',
            'position' => Position::Employer,
            'department' => 'IT',
            'email' => 'ibrahim@example.com',
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Mariam',
            'last_name' => 'Keita',
            'phone' => '33445566',
            'position' => Position::Employer,
            'department' => 'Finance',
            'email' => 'mariam@example.com',
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Ousmane',
            'last_name' => 'Coulibaly',
            'phone' => '44556677',
            'position' => Position::Employer,
            'department' => 'Marketing',
            'email' => 'ousmane@example.com',
        ]);

        // Create 5 more random employees with users
        for ($i = 0; $i < 5; $i++) {
            $this->createRandomEmployeeWithUser();
        }
    }

    /**
     * Create an employee with associated user following the authentication system logic.
     */
    private function createEmployeeWithUser(array $employeeData): void
    {
        DB::transaction(function () use ($employeeData) {
            // Normalize last_name for login (lowercase, without accents)
            $normalizedLastName = strtolower(Str::ascii($employeeData['last_name']));

            // Generate username: normalized_last_name@phone.org
            $username = $normalizedLastName.'@'.$employeeData['phone'].'.org';

            // Handle username collisions
            $counter = 1;
            $originalUsername = $username;
            while (User::where('username', $username)->exists()) {
                $username = $normalizedLastName.$counter.'@'.$employeeData['phone'].'.org';
                $counter++;
            }

            // Generate default password: ML+phone
            $password = 'ML'.$employeeData['phone'];

            // Create User
            $user = User::create([
                'name' => $employeeData['first_name'].' '.$employeeData['last_name'],
                'username' => $username,
                'email' => $employeeData['email'] ?? null,
                'password' => Hash::make($password),
                'password_changed_at' => null, // First login required
            ]);

            // Generate employee_id
            $lastEmployee = Employee::orderBy('id', 'desc')->first();
            $employeeNumber = $lastEmployee ? ((int) str_replace('EMP', '', $lastEmployee->employee_id)) + 1 : 1;
            $employeeId = 'EMP'.str_pad((string) $employeeNumber, 4, '0', STR_PAD_LEFT);

            // Create Employee
            Employee::create([
                'user_id' => $user->id,
                'employee_id' => $employeeId,
                'first_name' => $employeeData['first_name'],
                'last_name' => $employeeData['last_name'], // Store original value
                'email' => $employeeData['email'] ?? null,
                'phone' => $employeeData['phone'],
                'position' => $employeeData['position'],
                'department' => $employeeData['department'] ?? null,
                'status' => $employeeData['status'] ?? EmployeeStatus::Active,
            ]);
        });
    }

    /**
     * Create a random employee with associated user.
     */
    private function createRandomEmployeeWithUser(): void
    {
        $phone = fake()->numerify('########');
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        $this->createEmployeeWithUser([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'position' => fake()->randomElement(Position::cases()),
            'department' => fake()->optional()->randomElement(['IT', 'RH', 'Finance', 'Marketing', 'Ventes', 'Production']),
            'email' => fake()->optional()->safeEmail(),
            'status' => EmployeeStatus::Active,
        ]);
    }
}
