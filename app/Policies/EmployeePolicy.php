<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any employees.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-employee');
    }

    /**
     * Determine whether the user can view the employee.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-employee');
    }

    /**
     * Determine whether the user can create employees.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-employee');
    }

    /**
     * Determine whether the user can update the employee.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-employee');
    }

    /**
     * Determine whether the user can delete the employee.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-employee');
    }

    /**
     * Determine whether the user can restore the employee.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Employee  $employee
     * @return mixed
     */
    public function restore(User $user, Employee $employee)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the employee.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Employee  $employee
     * @return mixed
     */
    public function forceDelete(User $user, Employee $employee)
    {
        //
    }
}
