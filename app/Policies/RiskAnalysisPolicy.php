<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RiskAnalysis;
use Illuminate\Auth\Access\HandlesAuthorization;

class RiskAnalysisPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any risk analyses.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAccess('index-riskanalysis');
    }

    /**
     * Determine whether the user can view the risk analysis.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->hasAccess('show-riskanalysis');
    }

    /**
     * Determine whether the user can create risk analyses.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAccess('store-riskanalysis');
    }

    /**
     * Determine whether the user can update the risk analysis.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->hasAccess('update-riskanalysis');
    }

    /**
     * Determine whether the user can delete the risk analysis.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->hasAccess('destroy-riskanalysis');
    }

    /**
     * Determine whether the user can restore the risk analysis.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RiskAnalysis  $riskAnalysis
     * @return mixed
     */
    public function restore(User $user, RiskAnalysis $riskAnalysis)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the risk analysis.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\RiskAnalysis  $riskAnalysis
     * @return mixed
     */
    public function forceDelete(User $user, RiskAnalysis $riskAnalysis)
    {
        //
    }
}
