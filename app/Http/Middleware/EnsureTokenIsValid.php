<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;


class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        // dd($request->bearerToken());
        // dd(Auth::guard('app-api')->user());
        $token = $request->bearerToken();
        if(empty($token)){
            // return \response
            return response()->json([
                'status' => 'error',
                'message' => 'Please use a Bearer Token',
            ]);
        }elseif(empty(Auth::guard('app-api')->user())){
            return response()->json([
                'status' => 'error',
                'message' => 'Please use a valid Token',
            ]);
        }
        return $next($request);
    }
}
