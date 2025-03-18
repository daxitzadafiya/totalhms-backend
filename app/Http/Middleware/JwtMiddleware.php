<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Response;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware extends BaseMiddleware
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
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                $errorMessage = 'Invalid Token';
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                $errorMessage = 'Expired Token';
            }else{
                $errorMessage = 'Authorization Token not found';
            }
            $response = array(
                'error' => true,
                'data' => null ,
                'errors' => $errorMessage
            );
            return Response::json($response,401);
        }
        return $next($request);
    }
}
