<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Sites\StoreSiteRequest;
use App\Http\Requests\Api\Sites\UpdateSiteRequest;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class SiteController extends Controller
{
    #[OA\Get(
        path: '/api/sites',
        summary: 'List user sites',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Sites'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SiteSchema')
                )
            ),
        ]
    )]
    /**
     * Display a listing of the user's sites.
     */
    public function index(Request $request): array
    {
        $userId = $request->user()->id;
        $cacheKey = "user_sites_v3:{$userId}";

        return Cache::remember($cacheKey, 60, static function () use ($request) {
            $sites = $request->user()->sites()
                ->with('configurations.checkType')
                ->latest()
                ->get();

            $data = SiteResource::collection($sites)->resolve();

            // Force deep conversion to plain arrays to avoid serialization issues with nested resources/carbon
            return json_decode(json_encode($data, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        });
    }

    #[OA\Post(
        path: '/api/sites',
        description: 'Creates a new site for monitoring. You can optionally pass an array of checks to be configured for this site.',
        summary: 'Store a new site',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreSiteRequest')
        ),
        tags: ['Sites'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Site created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SiteSchema')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed'
            ),
        ]
    )]
    /**
     * Store a newly created site in storage.
     *
     * @throws \Throwable
     */
    public function store(StoreSiteRequest $request): SiteResource
    {
        return DB::transaction(static function () use ($request) {
            $site = $request->user()->sites()->create($request->safe()->except('checks'));

            if ($request->has('checks')) {
                foreach ($request->checks as $check) {
                    $site->configurations()->create([
                        'check_type_id' => $check['check_type_id'],
                        'params' => $check['params'] ?? null,
                    ]);
                }
            }

            self::clearUserSitesCache($request->user()->id);

            return new SiteResource($site->load('configurations.checkType'));
        });
    }

    #[OA\Get(
        path: '/api/sites/{site}',
        summary: 'Get site record',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Sites'],
        parameters: [
            new OA\Parameter(
                name: 'site',
                description: 'The site ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/SiteSchema')
            ),
            new OA\Response(
                response: 404,
                description: 'Site not found'
            ),
        ]
    )]
    /**
     * Display the specified site.
     */
    public function show(Request $request, Site $site): SiteResource
    {
        if ($site->user_id !== $request->user()->id) {
            abort(404);
        }

        return new SiteResource($site->load('configurations.checkType'));
    }

    #[OA\Put(
        path: '/api/sites/{site}',
        summary: 'Update existing site',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateSiteRequest')
        ),
        tags: ['Sites'],
        parameters: [
            new OA\Parameter(
                name: 'site',
                description: 'The site ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Site updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SiteSchema')
            ),
            new OA\Response(
                response: 404,
                description: 'Site not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed'
            ),
        ]
    )]
    /**
     * Update the specified site in storage.
     *
     * @throws \Throwable
     */
    public function update(UpdateSiteRequest $request, Site $site): SiteResource
    {
        if ($site->user_id !== $request->user()->id) {
            abort(404);
        }

        return DB::transaction(static function () use ($request, $site) {
            $site->update($request->safe()->except('checks'));

            if ($request->has('checks')) {
                // For simplicity, we'll sync by deleting and re-creating if ID is missing.
                // Or better, handle matching IDs.
                $updatedCheckIds = [];
                foreach ($request->checks as $checkData) {
                    if (isset($checkData['id'])) {
                        $config = $site->configurations()->findOrFail($checkData['id']);
                        $config->update($checkData);
                    } else {
                        $config = $site->configurations()->create($checkData);
                    }
                    $updatedCheckIds[] = $config->id;
                }

                // Optionally delete configs not in the request:
                $site->configurations()->whereNotIn('id', $updatedCheckIds)->delete();
            }

            self::clearUserSitesCache($request->user()->id);

            return new SiteResource($site->load('configurations.checkType'));
        });
    }

    #[OA\Delete(
        path: '/api/sites/{site}',
        summary: 'Delete site',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Sites'],
        parameters: [
            new OA\Parameter(
                name: 'site',
                description: 'The site ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Site deleted successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Site not found'
            ),
        ]
    )]
    /**
     * Remove the specified site from storage.
     */
    public function destroy(Request $request, Site $site): Response
    {
        if ($site->user_id !== $request->user()->id) {
            abort(404);
        }

        $userId = $site->user_id;

        $site->delete();

        self::clearUserSitesCache($userId);

        return response()->noContent();
    }

    /**
     * Clear the site cache for a specific user.
     */
    public static function clearUserSitesCache(int $userId): void
    {
        Cache::forget("user_sites_v3:{$userId}");
    }
}
