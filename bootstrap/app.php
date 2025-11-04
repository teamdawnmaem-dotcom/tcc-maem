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
        //
    })

    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'api.key' => \App\Http\Middleware\ApiKeyAuth::class,
    ]);
    })
    
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle 404 Not Found: logout and redirect to login for web; JSON for API
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Not Found'], 404);
            }
            if (auth()->check()) {
                auth()->logout();
            }
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            return redirect()->route('login')->with('error', 'Session expired or page not found. Please log in again.');
        });

        // Handle 419 CSRF token mismatch: logout and redirect to login for web; JSON for API
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Page Expired'], 419);
            }
            if (auth()->check()) {
                auth()->logout();
            }
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            return redirect()->route('login')->with('error', 'Page expired. Please log in again.');
        });

        // Handle generic HttpException with status 419 (some layers may wrap it)
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Page Expired'], 419);
            }
            if (auth()->check()) {
                auth()->logout();
            }
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            return redirect()->route('login')->with('error', 'Page expired. Please log in again.');
        });

        // Handle unauthenticated: redirect to login for web; JSON for API
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        });
    })->create();

    
