<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Deviation;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeviationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any deviations.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-deviation');
    }

    /**
     * Determine whether the user can view the deviation.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-deviation');
    }

    /**
     * Determine whether the user can create deviations.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-deviation');
    }

    /**
     * Determine whether the user can update the deviation.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-deviation');
    }

    /**
     * Determine whether the user can delete the deviation.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-deviation');
    }

    /**
     * Determine whether the user can restore the deviation.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Deviation  $deviation
     * @return mixed
     */
    public function restore(User $user, Deviation $deviation)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the deviation.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Deviation  $deviation
     * @return mixed
     */
    public function forceDelete(User $user, Deviation $deviation)
    {
        //
    }
}
