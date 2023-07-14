<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;

class LogLastUserActivity
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
        if(auth('api')->check()) {
            $expiresAt = Carbon::now()->addMinutes(5);
            Cache::put('user-is-online-' . auth('api')->user()->user_id, true, $expiresAt);
        }

        return $next($request);
    }
}
