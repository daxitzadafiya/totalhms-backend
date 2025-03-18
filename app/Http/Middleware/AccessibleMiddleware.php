<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Subscription;
use Closure;

class AccessibleMiddleware extends Controller
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $module = [])
    {
        if($request->objectType){
            $access = $request->objectType;
        }elseif($request->objectTypeArray){
            $access = $request->objectTypeArray[0];
        }else{
            $access = $module;
        }
        if ($request->user()->role->level === Role::COMPANY_ROLE_LEVEL) {
            $subscription = $request->user()->planActive;

            // For Now: 
            if (!$subscription) {
                return $next($request);
            }

            $lastSubscriptions = Subscription::where('company_id', $request->user()->company_id)->select('plan_detail')->whereNotNull('deactivated_at')->whereNull('addon_id')->get();
            
            if($subscription && count($lastSubscriptions) > 0){
                $lastPlanAccess = [];
                foreach ($lastSubscriptions as $lastSubscription)
                {
                    $lastPlanAccess = array_merge(array_filter($lastSubscription->plan_detail['plan_detail']),$lastPlanAccess);
                } 
                $diffAaccess = array_diff_assoc($lastPlanAccess,$subscription->plan_detail['plan_detail']);
            }

            if ($subscription && $subscription->plan_detail['plan_detail'][$access] || @$diffAaccess[$access]) {
                return $next($request);
            }
            return $this->responseException('The module is not planned to be accessed.', 401);
        }
        return $next($request);
    }
}