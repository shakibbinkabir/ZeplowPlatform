<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api_key'       => \App\Http\Middleware\ValidateApiKey::class,
            'site_key'      => \App\Http\Middleware\ValidateSiteKey::class,
            'build_agent'   => \App\Http\Middleware\ResolveBuildAgent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // This is an API-only app — always render JSON, never HTML error pages.
        $exceptions->shouldRenderJsonWhen(fn () => true);

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error'  => 'Validation failed',
                'fields' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Not found'], 404);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json(['error' => 'Not found'], 404);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['error' => 'Forbidden'], 403);
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
            return response()->json(['error' => 'Too many requests'], 429);
        });
    })->create();
