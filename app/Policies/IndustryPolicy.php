<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Industry;
use Illuminate\Auth\Access\HandlesAuthorization;

class IndustryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any industries.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-industry');
    }

    /**
     * Determine whether the user can view the industry.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-industry');
    }

    /**
     * Determine whether the user can create industries.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-industry');
    }

    /**
     * Determine whether the user can update the industry.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-industry');
    }

    /**
     * Determine whether the user can delete the industry.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-industry');
    }

    /**
     * Determine whether the user can restore the industry.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Industry  $industry
     * @return mixed
     */
    public function restore(User $user, Industry $industry)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the industry.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Industry  $industry
     * @return mixed
     */
    public function forceDelete(User $user, Industry $industry)
    {
        //
    }
}
