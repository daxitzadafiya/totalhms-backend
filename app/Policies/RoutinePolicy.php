<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Routine;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoutinePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any routines.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-routine');
    }

    /**
     * Determine whether the user can view the routine.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-routine');
    }

    /**
     * Determine whether the user can create routines.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-routine');
    }

    /**
     * Determine whether the user can update the routine.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-routine');
    }

    /**
     * Determine whether the user can delete the routine.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-routine');
    }

    /**
     * Determine whether the user can restore the routine.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Routine  $routine
     * @return mixed
     */
    public function restore(User $user, Routine $routine)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the routine.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Routine  $routine
     * @return mixed
     */
    public function forceDelete(User $user, Routine $routine)
    {
        //
    }
}
