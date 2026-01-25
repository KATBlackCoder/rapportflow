<?php

namespace App\Actions\Fortify;

use App\Enums\EmployeeStatus;
use App\Enums\Position;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'size:8', 'regex:/^[0-9]{8}$/', Rule::unique('employees', 'phone')],
            'position' => ['required', Rule::in(['employer', 'superviseur', 'chef_superviseur', 'manager'])],
            'department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            // Normalize last_name for login (lowercase, without accents)
            $normalizedLastName = strtolower(Str::ascii($input['last_name']));

            // Generate username: normalized_last_name@phone.org
            $username = $normalizedLastName.'@'.$input['phone'].'.org';

            // Handle username collisions
            $originalUsername = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $normalizedLastName.$counter.'@'.$input['phone'].'.org';
                $counter++;
            }

            // Generate default password: ML+phone
            $password = 'ML'.$input['phone'];

            // Create User
            $user = User::create([
                'name' => $input['first_name'].' '.$input['last_name'],
                'username' => $username,
                'email' => $input['email'] ?? null,
                'password' => Hash::make($password),
                'password_changed_at' => null,
            ]);

            // Generate employee_id
            $lastEmployee = Employee::orderBy('id', 'desc')->first();
            $employeeNumber = $lastEmployee ? ((int) str_replace('EMP', '', $lastEmployee->employee_id)) + 1 : 1;
            $employeeId = 'EMP'.str_pad((string) $employeeNumber, 4, '0', STR_PAD_LEFT);

            // Create Employee
            Employee::create([
                'user_id' => $user->id,
                'employee_id' => $employeeId,
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'], // Store original value
                'email' => $input['email'] ?? null,
                'phone' => $input['phone'],
                'position' => Position::from($input['position']),
                'department' => $input['department'] ?? null,
                'status' => EmployeeStatus::Active,
            ]);

            return $user;
        });
    }
}
