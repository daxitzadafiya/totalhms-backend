<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Checklist;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChecklistPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any checklists.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-checklist');
    }

    /**
     * Determine whether the user can view the checklist.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-checklist');
    }

    /**
     * Determine whether the user can create checklists.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-checklist');
    }

    /**
     * Determine whether the user can update the checklist.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-checklist');
    }

    /**
     * Determine whether the user can delete the checklist.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-checklist');
    }

    /**
     * Determine whether the user can restore the checklist.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Checklist  $checklist
     * @return mixed
     */
    public function restore(User $user, Checklist $checklist)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the checklist.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Checklist  $checklist
     * @return mixed
     */
    public function forceDelete(User $user, Checklist $checklist)
    {
        //
    }
}
