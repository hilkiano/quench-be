<?php

namespace App\Http\Middleware;

use App\Models\Backoffice\Session;
use App\Traits\GeneralHelpers;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BackofficeMiddleware
{
    use GeneralHelpers;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasHeader("X-Backoffice-Session")) {
            return $this->jsonResponse(status: false, code: 401, message: "Session header not found.");
        }

        $session = Session::where("id", $request->header("X-Backoffice-Session"))->first();
        if (!$session) {
            return $this->jsonResponse(status: false, code: 401, message: "Invalid session ID.");
        }

        return $next($request);
    }
}
