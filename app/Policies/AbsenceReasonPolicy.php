<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AbsenceReason;
use Illuminate\Auth\Access\HandlesAuthorization;

class AbsenceReasonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any risk element sources.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-absencereason');
    }

    /**
     * Determine whether the user can view the risk element source.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-absencereason');
    }

    /**
     * Determine whether the user can create risk element sources.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-absencereason');
    }

    /**
     * Determine whether the user can update the risk element source.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-absencereason');
    }

    /**
     * Determine whether the user can delete the risk element source.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-absencereason');
    }

    /**
     * Determine whether the user can restore the risk element source.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AbsenceReason  $absenceReason
     * @return mixed
     */
    public function restore(User $user, AbsenceReason $absenceReason)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the risk element source.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AbsenceReason  $absenceReason
     * @return mixed
     */
    public function forceDelete(User $user, AbsenceReason $absenceReason)
    {
        //
    }
}
