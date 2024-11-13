<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Http\Middleware\CheckLocale;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append([
            CheckLocale::class
        ]);
        $middleware->encryptCookies(except: [
            'login_session',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (JWTException $e) {
            return response()->json([
                "status" => false,
                "data" => null,
                "message" => $e->getMessage(),
                "trace" => env("APP_DEBUG") ? $e->getTrace() : null,
                "code" => 401,
            ], 401);
        });
        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json([
                "status" => false,
                "data" => null,
                "message" => $e->getMessage(),
                "trace" => env("APP_DEBUG") ? $e->getTrace() : null,
                "code" => 404,
            ], 404);
        });
        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                "status" => false,
                "data" => $e->errors(),
                "message" => $e->getMessage(),
                "trace" => env("APP_DEBUG") ? $e->getTrace() : null,
                "code" => 422,
            ], 422);
        });
        $exceptions->render(function (\Exception $e) {
            return response()->json([
                "status" => false,
                "data" => null,
                "message" => $e->getMessage(),
                "trace" => env("APP_DEBUG") ? $e->getTrace() : null,
                "code" => 500,
            ], 500);
        });
    })->create();
