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

        // Créer la hiérarchie par département
        // Structure: Manager -> ChefSuperviseur -> Superviseur -> Employer

        // Département Marketing
        $managerIT = $this->createEmployeeWithUser([
            'first_name' => 'Amadou',
            'last_name' => 'Traoré',
            'phone' => '12345678',
            'position' => Position::Manager,
            'department' => 'Marketing',
            'email' => 'amadou@example.com',
        ]);

        $chefSuperviseurIT = $this->createEmployeeWithUser([
            'first_name' => 'Fatoumata',
            'last_name' => 'Diallo',
            'phone' => '87654321',
            'position' => Position::ChefSuperviseur,
            'department' => 'Marketing',
            'email' => 'fatoumata@example.com',
            'manager_id' => $managerIT->id,
        ]);

        $superviseurIT = $this->createEmployeeWithUser([
            'first_name' => 'Moussa',
            'last_name' => 'Cissé',
            'phone' => '11223344',
            'position' => Position::Superviseur,
            'department' => 'Marketing',
            'email' => 'moussa@example.com',
            'manager_id' => $chefSuperviseurIT->id,
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Ibrahim',
            'last_name' => 'Konaté',
            'phone' => '22334455',
            'position' => Position::Employer,
            'department' => 'Marketing',
            'email' => 'ibrahim@example.com',
            'supervisor_id' => $superviseurIT->id,
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Sekou',
            'last_name' => 'Toure',
            'phone' => '99887766',
            'position' => Position::Employer,
            'department' => 'Marketing',
            'email' => 'sekou@example.com',
            'supervisor_id' => $superviseurIT->id,
        ]);

        // Département Marketing
        $managerVentes = $this->createEmployeeWithUser([
            'first_name' => 'Aissata',
            'last_name' => 'Sangaré',
            'phone' => '55667788',
            'position' => Position::Manager,
            'department' => 'Marketing',
            'email' => 'aissata@example.com',
        ]);

        $chefSuperviseurVentes = $this->createEmployeeWithUser([
            'first_name' => 'Bakary',
            'last_name' => 'Diakite',
            'phone' => '66778899',
            'position' => Position::ChefSuperviseur,
            'department' => 'Marketing',
            'email' => 'bakary@example.com',
            'manager_id' => $managerVentes->id,
        ]);

        $superviseurVentes = $this->createEmployeeWithUser([
            'first_name' => 'Kadiatou',
            'last_name' => 'Camara',
            'phone' => '77889900',
            'position' => Position::Superviseur,
            'department' => 'Marketing',
            'email' => 'kadiatou@example.com',
            'manager_id' => $chefSuperviseurVentes->id,
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Ousmane',
            'last_name' => 'Coulibaly',
            'phone' => '44556677',
            'position' => Position::Employer,
            'department' => 'Marketing',
            'email' => 'ousmane@example.com',
            'supervisor_id' => $superviseurVentes->id,
        ]);

        // Département Marketing
        $managerMarketing = $this->createEmployeeWithUser([
            'first_name' => 'Aminata',
            'last_name' => 'Kone',
            'phone' => '11112222',
            'position' => Position::Manager,
            'department' => 'Marketing',
            'email' => 'aminata@example.com',
        ]);

        $superviseurMarketing = $this->createEmployeeWithUser([
            'first_name' => 'Modibo',
            'last_name' => 'Sidibe',
            'phone' => '22223333',
            'position' => Position::Superviseur,
            'department' => 'Marketing',
            'email' => 'modibo@example.com',
            'manager_id' => $managerMarketing->id,
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Fanta',
            'last_name' => 'Ba',
            'phone' => '33334444',
            'position' => Position::Employer,
            'department' => 'Marketing',
            'email' => 'fanta@example.com',
            'supervisor_id' => $superviseurMarketing->id,
        ]);

        // Département Marketing
        $managerFinance = $this->createEmployeeWithUser([
            'first_name' => 'Mamadou',
            'last_name' => 'Kante',
            'phone' => '44445555',
            'position' => Position::Manager,
            'department' => 'Marketing',
            'email' => 'mamadou@example.com',
        ]);

        $superviseurFinance = $this->createEmployeeWithUser([
            'first_name' => 'Mariam',
            'last_name' => 'Keita',
            'phone' => '33445566',
            'position' => Position::Superviseur,
            'department' => 'Marketing',
            'email' => 'mariam@example.com',
            'manager_id' => $managerFinance->id,
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Youssouf',
            'last_name' => 'Diawara',
            'phone' => '55556666',
            'position' => Position::Employer,
            'department' => 'Marketing',
            'email' => 'youssouf@example.com',
            'supervisor_id' => $superviseurFinance->id,
        ]);

        // Département Marketing
        $managerRH = $this->createEmployeeWithUser([
            'first_name' => 'Hawa',
            'last_name' => 'Doumbia',
            'phone' => '66667777',
            'position' => Position::Manager,
            'department' => 'Marketing',
            'email' => 'hawa@example.com',
        ]);

        $chefSuperviseurRH = $this->createEmployeeWithUser([
            'first_name' => 'Boubacar',
            'last_name' => 'Maiga',
            'phone' => '77778888',
            'position' => Position::ChefSuperviseur,
            'department' => 'Marketing',
            'email' => 'boubacar@example.com',
            'manager_id' => $managerRH->id,
        ]);

        $superviseurRH = $this->createEmployeeWithUser([
            'first_name' => 'Awa',
            'last_name' => 'Traore',
            'phone' => '88889999',
            'position' => Position::Superviseur,
            'department' => 'Marketing',
            'email' => 'awa@example.com',
            'manager_id' => $chefSuperviseurRH->id,
        ]);

        $this->createEmployeeWithUser([
            'first_name' => 'Ibrahima',
            'last_name' => 'Sall',
            'phone' => '99990000',
            'position' => Position::Employer,
            'department' => 'Marketing',
            'email' => 'ibrahima@example.com',
            'supervisor_id' => $superviseurRH->id,
        ]);
    }

    /**
     * Create an employee with associated user following the authentication system logic.
     *
     * @return Employee
     */
    private function createEmployeeWithUser(array $employeeData): Employee
    {
        return DB::transaction(function () use ($employeeData) {
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
            return Employee::create([
                'user_id' => $user->id,
                'employee_id' => $employeeId,
                'first_name' => $employeeData['first_name'],
                'last_name' => $employeeData['last_name'], // Store original value
                'email' => $employeeData['email'] ?? null,
                'phone' => $employeeData['phone'],
                'position' => $employeeData['position'],
                'department' => $employeeData['department'] ?? null,
                'manager_id' => $employeeData['manager_id'] ?? null,
                'supervisor_id' => $employeeData['supervisor_id'] ?? null,
                'status' => $employeeData['status'] ?? EmployeeStatus::Active,
            ]);
        });
    }

}
