<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;

class SetLang2
{

    public function handle($request, Closure $next)
    {
        var_dump(112233);
        return $next($request);
    }

}















