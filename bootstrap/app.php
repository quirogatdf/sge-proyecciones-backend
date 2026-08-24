<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API-only: SIEMPRE JSON 401 en auth fallida, sin importar headers.
        // Esto evita el fallback a route('login') en Handler::unauthenticated().
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            return response()->json(['message' => 'No autenticado.'], 401);
        });
    })
    ->booted(function (): void {
        // Evitar que Authenticate::redirectTo() explote con route('login') inexistente.
        \Illuminate\Auth\Middleware\Authenticate::redirectUsing(
            fn (\Illuminate\Http\Request $request) => null
        );
    })->create();
