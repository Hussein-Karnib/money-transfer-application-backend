<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'         => \App\Http\Middleware\RoleMiddleware::class,
            'kyc_verified' => \App\Http\Middleware\KycVerifiedMiddleware::class,
     ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle authentication exceptions for API routes
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }
        });

        // Handle 401/403 errors for API routes
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Unauthorized',
                ], $status);
            }
        });
    })
    ->create();


    