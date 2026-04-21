<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerifyTurnstile
{
    public function handle(Request $request, Closure $next)
    {
        $isTurnstileActive = config('services.turnstile.active');

        Log::info('Turnstile: Incoming Request', [
            'url' => $request->fullUrl(),
            'has_token' => $request->has('cf_turnstile_response'),
            'env' => app()->environment(),
            'active' => $isTurnstileActive,
            'cf_turnstile_response' => $request->input('cf_turnstile_response'),
        ]);

        if (! $isTurnstileActive || app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        $token = $request->input('cf_turnstile_response');

        $secret = config('services.turnstile.secret_key');

        if (empty($token)) {
            Log::warning('Turnstile: Missing token', ['ip' => $request->ip()]);
            return response()->json([
                'error' => 'Missing Turnstile token.',
            ], 422);
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Turnstile verification request failed', ['exception' => $e]);

            return response()->json([
                'error' => 'Turnstile verification request failed. Please try again later.',
            ], 503);
        }

        $result = $response->json();

        if (! ($result['success'] ?? false)) {
            Log::warning('Turnstile verification failed', [
                'ip' => $request->ip(),
                'response' => $result,
            ]);

            return response()->json([
                'error' => 'Turnstile verification failed.',
                'details' => $result['error-codes'] ?? [],
            ], 403);
        }

        return $next($request);
    }
}
