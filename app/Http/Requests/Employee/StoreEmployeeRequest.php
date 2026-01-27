<?php

namespace App\Http\Requests\Employee;

use App\Enums\Position;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $position = $this->input('position');
        $department = $this->input('department');

        $rules = [
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_id')],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')],
            'phone' => ['required', 'string', 'size:8', 'regex:/^[0-9]{8}$/', Rule::unique('employees', 'phone')],
            'position' => ['required', Rule::in(['employer', 'superviseur', 'chef_superviseur', 'manager'])],
            'department' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')],
            'supervisor_id' => ['nullable', Rule::exists('employees', 'id')],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'terminated'])],
            'user_id' => ['nullable', Rule::exists('users', 'id'), Rule::unique('employees', 'user_id')],
        ];

        // Règles spécifiques selon la position
        if ($position === 'employer') {
            // Les employés doivent avoir un superviseur (même département)
            $rules['supervisor_id'] = [
                'required',
                Rule::exists('employees', 'id')
                    ->where('position', Position::Superviseur->value)
                    ->where('department', $department),
            ];
            $rules['manager_id'] = ['nullable']; // Pas de manager pour les employés
        }

        if ($position === 'superviseur') {
            // Les superviseurs peuvent avoir un chef_superviseur (même département)
            $rules['manager_id'] = [
                'nullable',
                Rule::exists('employees', 'id')
                    ->where('position', Position::ChefSuperviseur->value)
                    ->where('department', $department),
            ];
            $rules['supervisor_id'] = ['nullable']; // Pas de supervisor pour les superviseurs
        }

        if ($position === 'chef_superviseur') {
            // Les chefs superviseurs peuvent avoir un manager (même département)
            $rules['manager_id'] = [
                'nullable',
                Rule::exists('employees', 'id')
                    ->where('position', Position::Manager->value)
                    ->where('department', $department),
            ];
            $rules['supervisor_id'] = ['nullable']; // Pas de supervisor pour les chefs superviseurs
        }

        if ($position === 'manager') {
            // Les managers n'ont ni manager ni supervisor
            $rules['manager_id'] = ['nullable'];
            $rules['supervisor_id'] = ['nullable'];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $position = $this->input('position');
            $department = $this->input('department');
            $managerId = $this->input('manager_id');
            $supervisorId = $this->input('supervisor_id');

            // Validation: employer doit avoir un supervisor
            if ($position === 'employer' && ! $supervisorId) {
                $validator->errors()->add(
                    'supervisor_id',
                    'Un employé doit avoir un superviseur.'
                );
            }

            // Validation: supervisor doit être dans le même département que l'employé
            if ($position === 'employer' && $supervisorId) {
                $supervisor = Employee::find($supervisorId);
                if ($supervisor && $supervisor->department !== $department) {
                    $validator->errors()->add(
                        'supervisor_id',
                        'Le superviseur doit être dans le même département que l\'employé.'
                    );
                }
            }

            // Validation: manager doit être dans le même département
            if ($managerId) {
                $manager = Employee::find($managerId);
                if ($manager && $manager->department !== $department) {
                    $validator->errors()->add(
                        'manager_id',
                        'Le manager doit être dans le même département.'
                    );
                }
            }
        });
    }
}
