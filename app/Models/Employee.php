<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\Position;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'department',
        'manager_id',
        'supervisor_id',
        'salary',
        'hire_date',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => Position::class,
            'status' => EmployeeStatus::class,
            'salary' => 'decimal:2',
            'hire_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the manager of the employee.
     * For superviseur: manager is chef_superviseur (same department)
     * For chef_superviseur: manager is manager (same department)
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get the supervisor of the employee.
     * For employer: supervisor is superviseur (required)
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /**
     * Get the subordinates of the employee (via manager_id).
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Get the employees supervised by this employee (via supervisor_id).
     * Only for superviseurs supervising employers.
     */
    public function supervisedEmployees(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    /**
     * Get the supervisors that report to this employee (via manager_id).
     * Only for chef_superviseurs managing superviseurs (same department).
     */
    public function supervisedSupervisors(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id')
            ->where('position', Position::Superviseur->value);
    }

    /**
     * Get the chef_superviseurs that report to this employee (via manager_id).
     * Only for managers managing chef_superviseurs (same department).
     */
    public function supervisedChefSuperviseurs(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id')
            ->where('position', Position::ChefSuperviseur->value);
    }

    /**
     * Scope to get employees with manager in the same department.
     * This scope filters employees where their manager's department matches their own department.
     */
    public function scopeWithValidManagerDepartment($query)
    {
        return $query->join('employees as managers', 'employees.manager_id', '=', 'managers.id')
            ->whereColumn('employees.department', 'managers.department')
            ->select('employees.*');
    }

    /**
     * Scope to get employees by position.
     */
    public function scopeByPosition($query, Position $position)
    {
        return $query->where('position', $position->value);
    }

    /**
     * Scope to get employees in the same department.
     */
    public function scopeInDepartment($query, ?string $department)
    {
        if ($department === null) {
            return $query->whereNull('department');
        }

        return $query->where('department', $department);
    }

    /**
     * Get the display last name (uppercase, without accents).
     */
    public function getDisplayLastNameAttribute(): string
    {
        return strtoupper(Str::ascii($this->last_name));
    }

    /**
     * Normalize last name for login (lowercase, without accents).
     */
    public function normalizeLastNameForLogin(): string
    {
        return strtolower(Str::ascii($this->last_name));
    }
}
