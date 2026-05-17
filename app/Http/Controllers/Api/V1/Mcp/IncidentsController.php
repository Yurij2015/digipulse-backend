<?php

namespace App\Http\Controllers\Api\V1\Mcp;

use App\Http\Controllers\Controller;
use App\Models\CheckResult;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer'],
            'site_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $userId = $request->user()->id;
        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;

        $siteIds = Site::where('user_id', $userId)
            ->when(isset($validated['project_id']), fn ($q) => $q->where('project_id', $validated['project_id']))
            ->when(isset($validated['site_id']), fn ($q) => $q->where('id', $validated['site_id']))
            ->pluck('id');

        $query = CheckResult::whereIn('site_id', $siteIds)
            ->where('status', 'down')
            ->with('site:id,name,url,project_id')
            ->when(isset($validated['from']), fn ($q) => $q->where('checked_at', '>=', $validated['from']))
            ->when(isset($validated['to']), fn ($q) => $q->where('checked_at', '<=', $validated['to']))
            ->orderBy('checked_at', 'desc')
            ->limit($limit)
            ->offset($offset);

        $incidents = $query->get()->map(fn ($r) => [
            'site_id' => $r->site_id,
            'site_name' => $r->site?->name,
            'site_url' => $r->site?->url,
            'project_id' => $r->site?->project_id,
            'checked_at' => $r->checked_at->toIso8601String(),
            'response_time_ms' => $r->response_time_ms,
            'error' => $r->error_message,
        ]);

        return response()->json([
            'data' => [
                'incidents' => $incidents,
                'total' => $incidents->count(),
            ],
        ]);
    }
}