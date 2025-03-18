<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Goal;
use Illuminate\Auth\Access\HandlesAuthorization;

class GoalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any goals.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-goal');
    }

    /**
     * Determine whether the user can view the goal.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-goal');
    }

    /**
     * Determine whether the user can create goals.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-goal');
    }

    /**
     * Determine whether the user can update the goal.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-goal');
    }

    /**
     * Determine whether the user can delete the goal.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-goal');
    }

    /**
     * Determine whether the user can restore the goal.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Goal  $goal
     * @return mixed
     */
    public function restore(User $user, Goal $goal)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the goal.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Goal  $goal
     * @return mixed
     */
    public function forceDelete(User $user, Goal $goal)
    {
        //
    }
}
