<?php

namespace App\Policies;

use App\Models\User;
use App\Models\InstructionActivity;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstructionActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any instruction activities.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-instructionactivity');
    }

    /**
     * Determine whether the user can view the instruction activity.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-instructionactivity');
    }

    /**
     * Determine whether the user can create instruction activities.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-instructionactivity');
    }

    /**
     * Determine whether the user can update the instruction activity.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-instructionactivity');
    }

    /**
     * Determine whether the user can delete the instruction activity.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-instructionactivity');
    }

    /**
     * Determine whether the user can restore the instruction activity.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\InstructionActivity  $instructionActivity
     * @return mixed
     */
    public function restore(User $user, InstructionActivity $instructionActivity)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the instruction activity.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\InstructionActivity  $instructionActivity
     * @return mixed
     */
    public function forceDelete(User $user, InstructionActivity $instructionActivity)
    {
        //
    }
}
