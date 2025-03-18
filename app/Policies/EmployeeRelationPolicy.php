<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EmployeeRelation;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeRelationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any employee relations.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-employeerelation');
    }

    /**
     * Determine whether the user can view the employee relation.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-employeerelation');
    }

    /**
     * Determine whether the user can create employee relations.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-employeerelation');
    }

    /**
     * Determine whether the user can update the employee relation.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-employeerelation');
    }

    /**
     * Determine whether the user can delete the employee relation.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-employeerelation');
    }

    /**
     * Determine whether the user can restore the employee relation.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EmployeeRelation  $employeeRelation
     * @return mixed
     */
    public function restore(User $user, EmployeeRelation $employeeRelation)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the employee relation.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\EmployeeRelation  $employeeRelation
     * @return mixed
     */
    public function forceDelete(User $user, EmployeeRelation $employeeRelation)
    {
        //
    }
}
