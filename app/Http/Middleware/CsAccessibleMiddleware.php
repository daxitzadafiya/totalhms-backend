<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Closure;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

class CsAccessibleMiddleware extends Controller
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

        if ($request->user()->role->level === Role::CS_ROLE_LEVEL) {

            $urlPath  = explode('/' , $request->path());
            $urlPath = $urlPath[2];
            
            if($urlPath === "plans" || $urlPath === "addons" || $urlPath === "coupons"){
                $url = 'billings';
            }elseif($urlPath === "email_contents"){
                $url = 'settings';
            }else{
                $url = $urlPath;
            }

            $permissions = [];

            foreach ($request->user()->role->csPermissions as $key => $permission) {
                $permissions[$permission['module']] = $permission['is_enabled'];
            }

            if(@$permissions[$url]){
                return $next($request);
            }
            return $this->responseException('Modules cannot be accessed.', 401);

        }
        return $next($request);
    }
}