<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalMonitorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = config('app.internal_monitor_key');

        if (! $key || $request->header('X-Monitor-Key') !== $key) {
            return response()->json(['message' => 'Unauthorized monitor access'], 401);
        }

        return $next($request);
    }
}
