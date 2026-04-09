<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('employee_access')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
