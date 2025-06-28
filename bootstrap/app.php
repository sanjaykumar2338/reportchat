<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Broadcasting;
use Illuminate\Console\Scheduling\Schedule; // ✅ 1. Add this import
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use App\Http\Middleware\AdminMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'sanctum' => EnsureFrontendRequestsAreStateful::class,
            'admin' => AdminMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withBroadcasting('reverb')
    
    // ✅ 2. Add the withSchedule() method here
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('reservations:generate-weekly')->daily();
    })
    
    ->create();