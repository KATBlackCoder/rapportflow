<?php

namespace App\Policies;

use App\Enums\Position;
use App\Models\Questionnaire;
use App\Models\User;

class QuestionnairePolicy
{
    /**
     * Determine whether the user can view any models.
     * Seuls les managers et chefs superviseurs peuvent voir la liste des questionnaires.
     */
    public function viewAny(User $user): bool
    {
        $employee = $user->employee;

        if (! $employee) {
            return false;
        }

        return in_array($employee->position, [Position::Manager, Position::ChefSuperviseur], true);
    }

    /**
     * Determine whether the user can view the model.
     * Seuls les managers et chefs superviseurs peuvent voir un questionnaire.
     */
    public function view(User $user, Questionnaire $questionnaire): bool
    {
        $employee = $user->employee;

        if (! $employee) {
            return false;
        }

        return in_array($employee->position, [Position::Manager, Position::ChefSuperviseur], true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $employee = $user->employee;

        if (! $employee) {
            return false;
        }

        return in_array($employee->position, [Position::Manager, Position::ChefSuperviseur], true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Questionnaire $questionnaire): bool
    {
        $userEmployee = $user->employee;

        if (! $userEmployee) {
            return false;
        }

        return in_array($userEmployee->position, [Position::Manager, Position::ChefSuperviseur], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Questionnaire $questionnaire): bool
    {
        $userEmployee = $user->employee;

        if (! $userEmployee) {
            return false;
        }

        return in_array($userEmployee->position, [Position::Manager, Position::ChefSuperviseur], true);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Questionnaire $questionnaire): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Questionnaire $questionnaire): bool
    {
        return false;
    }
}
