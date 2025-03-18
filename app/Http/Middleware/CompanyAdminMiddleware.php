<?php

namespace App\Http\Middleware;

use Closure;

class CompanyAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $role = $request->user()->user_role;

        if ($role == 1) {
            return $next($request);
        } else {
            return \Redirect::back()->withErrors( "You are not authorized");
        }
    }
}
