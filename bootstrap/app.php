<?php

use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsEmployee;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'isAdmin'    => IsAdmin::class,
            'isEmployee' => IsEmployee::class,
        ]);

        $middleware->redirectUsersTo(function () {
            $user = auth()->user();
            if ($user && $user->isAdmin()) {
                return route('admin.dashboard');
            }
            return route('employee.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
