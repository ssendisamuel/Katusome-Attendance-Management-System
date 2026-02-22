<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Providers\AuthServiceProvider;
use App\Console\Commands\SyncIdentityDuplicates;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        SyncIdentityDuplicates::class,
    ])
    ->withProviders([
        AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(LocaleMiddleware::class);
        $middleware->web(App\Http\Middleware\ForcePasswordChange::class);
        $middleware->web(App\Http\Middleware\CheckEnrollment::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session has expired. Please log in again.'], 419);
            }

            return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Your session has expired. Please log in again.'], 419);
                }
                return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
            }
        });
    })->create();
