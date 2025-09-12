<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use JWTAuth;
use Log;
use Symfony\Component\HttpFoundation\Response;

class CheckLoginSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasCookie("login_session")) {
            $request->headers->set('Authorization', 'Bearer ' . $request->cookie("login_session"));
        } else if ($request->headers->has("x-token")) {
            $request->headers->set('Authorization', 'Bearer ' . $request->header("x-token"));
        }

        // No expiration in JWT for now
        // $tokenExp = JWTAuth::parseToken()->getClaim("exp");
        // $minutesDiff = round(Carbon::createFromTimestamp($tokenExp)->diffInMinutes(now()) * -1);
        // $expiredOn = Carbon::createFromTimestamp($tokenExp)->setTimezone("Asia/Jakarta")->format("D F Y G:i:s");
        // if ($minutesDiff <= env("APP_TOKEN_REFRESH_MINUTE_LIMIT", 30)) {
        //     $refreshedToken = JWTAuth::parseToken()->refresh();
        //     $request->headers->set('Authorization', 'Bearer ' . $refreshedToken);
        //     $request->headers->set("x-token", $refreshedToken);
        //     $request->headers->set("x-token-ttl", $expiredOn);
        // }

        JWTAuth::parseToken()->authenticate();


        return $next($request);
    }
}
