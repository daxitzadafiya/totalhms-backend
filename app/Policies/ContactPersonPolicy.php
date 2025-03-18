<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ContactPerson;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPersonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contact people.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-contactperson');
    }

    /**
     * Determine whether the user can view the contact person.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-contactperson');
    }

    /**
     * Determine whether the user can create contact people.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-contactperson');
    }

    /**
     * Determine whether the user can update the contact person.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-contactperson');
    }

    /**
     * Determine whether the user can delete the contact person.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-contactperson');
    }

    /**
     * Determine whether the user can restore the contact person.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ContactPerson  $contactPerson
     * @return mixed
     */
    public function restore(User $user, ContactPerson $contactPerson)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the contact person.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ContactPerson  $contactPerson
     * @return mixed
     */
    public function forceDelete(User $user, ContactPerson $contactPerson)
    {
        //
    }
}
