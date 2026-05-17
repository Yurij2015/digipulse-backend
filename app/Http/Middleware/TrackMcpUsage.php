<?php

namespace App\Http\Middleware;

use App\Models\McpTokenUsage;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class TrackMcpUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken && $token->exists) {
            $endpoint = $request->route()?->getName() ?? 'unknown';
            McpTokenUsage::track($request->user()->id, $token->id, $endpoint);
        }

        return $response;
    }
}
