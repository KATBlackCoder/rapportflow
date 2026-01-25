<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        $employeeId = $this->route('employee')?->id ?? $this->route('id');

        return [
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_id')->ignore($employeeId)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'phone' => ['required', 'string', 'size:8', 'regex:/^[0-9]{8}$/', Rule::unique('employees', 'phone')->ignore($employeeId)],
            'position' => ['required', Rule::in(['employer', 'superviseur', 'chef_superviseur', 'manager'])],
            'department' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')->whereNot('id', $employeeId)],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'terminated'])],
            'user_id' => ['nullable', Rule::exists('users', 'id'), Rule::unique('employees', 'user_id')->ignore($employeeId)],
        ];
    }
}
