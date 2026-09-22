<?php

use App\Http\Middleware\Auth\AttachTokenFromCookie;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Added Cookie middleware to the API routes.
        $middleware->api(prepend: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
        ]);

        // Except access Token and refresh Token cookies from encryption
        // $middleware->encryptCookies(except: [
        //     'accessToken',
        //     'refreshToken',
        // ]);

        // custom middleware
        $middleware->alias([
            'auth.cookie' => AttachTokenFromCookie::class,
        ]);

        // Prepend CORS middleware so OPTIONS pre-flight requests are handled
        // before any route or auth middleware runs.
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // method not allow exception
        $exceptions->render(function (MethodNotAllowedHttpException $e) {
            return response()->json([
                "message" => $e->getMessage() ?? "Method not allowed",
            ], 405);
        });
    })->create();
