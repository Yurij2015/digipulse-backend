<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\SiteController;
use App\Models\CheckResult;
use App\Models\SiteCheckConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InternalCheckResultController extends Controller
{
    /**
     * Store a new check result from the monitor service.
     * @throws \Throwable
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'configuration_id' => ['required', 'exists:site_check_configurations,id'],
            'status' => ['required', 'string', 'in:up,down,slow'],
            'response_time_ms' => ['nullable', 'integer'],
            'error_message' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $config = SiteCheckConfiguration::with('site')->findOrFail($validated['configuration_id']);

        DB::transaction(function () use ($config, $validated) {
            // Update the configuration status
            $config->update([
                'last_status' => $validated['status'],
                'last_checked_at' => now(),
            ]);

            // Create the historical result record
            CheckResult::create([
                'site_id' => $config->site_id,
                'configuration_id' => $config->id,
                'status' => $validated['status'],
                'response_time_ms' => $validated['response_time_ms'] ?? null,
                'error_message' => $validated['error_message'] ?? null,
                'metadata' => $validated['metadata'] ?? null,
                'checked_at' => now(),
            ]);
        });

        // Invalidate the site cache for the user who owns the site
        SiteController::clearUserSitesCache($config->site->user_id);

        return response()->json(['success' => true]);
    }
}
