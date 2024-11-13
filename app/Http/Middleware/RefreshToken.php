<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (request()->hasHeader("x-token")) {
            $cookie = cookie(
                'login_session',
                request()->header("x-token"),
                config('jwt.ttl'),
                '/',
                env("APP_DOMAIN") === "" ? null : env("APP_DOMAIN"),
            );

            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}
