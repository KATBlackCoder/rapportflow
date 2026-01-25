<?php

namespace App\Http\Requests\Employee;

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
        return [
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_id')],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')],
            'phone' => ['required', 'string', 'size:8', 'regex:/^[0-9]{8}$/', Rule::unique('employees', 'phone')],
            'position' => ['required', Rule::in(['employer', 'superviseur', 'chef_superviseur', 'manager'])],
            'department' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'terminated'])],
            'user_id' => ['nullable', Rule::exists('users', 'id'), Rule::unique('employees', 'user_id')],
        ];
    }
}
