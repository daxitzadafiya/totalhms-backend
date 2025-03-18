<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Instruction;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstructionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any instructions.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-instruction');
    }

    /**
     * Determine whether the user can view the instruction.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-instruction');
    }

    /**
     * Determine whether the user can create instructions.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-instruction');
    }

    /**
     * Determine whether the user can update the instruction.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-instruction');
    }

    /**
     * Determine whether the user can delete the instruction.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-instruction');
    }

    /**
     * Determine whether the user can restore the instruction.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Instruction  $instruction
     * @return mixed
     */
    public function restore(User $user, Instruction $instruction)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the instruction.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Instruction  $instruction
     * @return mixed
     */
    public function forceDelete(User $user, Instruction $instruction)
    {
        //
    }
}
