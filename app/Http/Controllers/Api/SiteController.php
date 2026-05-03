<?php

namespace App\Http\Controllers\Api;

use App\Domain\Monitoring\Contracts\SiteManagementRepositoryInterface;
use App\Domain\Monitoring\Data\CreateSiteData;
use App\Domain\Monitoring\Models\Site;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Sites\StoreSiteRequest;
use App\Http\Requests\Api\Sites\UpdateSiteRequest;
use App\Http\Resources\SiteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class SiteController extends Controller
{
    private const string CACHE_VERSION = 'v7';

    public function __construct(
        private readonly SiteManagementRepositoryInterface $siteRepository
    ) {}

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
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;
        $version = self::CACHE_VERSION;
        $cacheKey = "user_sites_{$version}:{$userId}";

        $sitesData = Cache::remember($cacheKey, 60, function () use ($userId) {
            $sites = $this->siteRepository->findByUser($userId);

            return array_map(static fn (Site $site) => $site->toArray(), $sites);
        });

        $sites = array_map(fn (array $data) => $this->siteRepository->fromArray($data), $sitesData);

        return SiteResource::collection($sites);
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
     */
    public function store(StoreSiteRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $dto = new CreateSiteData(
                userId: $request->user()->id,
                name: $validated['name'],
                url: $validated['url'],
                updateInterval: $validated['update_interval'] ?? 5,
                isActive: $validated['is_active'] ?? true,
            );

            $site = $this->siteRepository->create($dto);

            if ($request->has('checks')) {
                $this->siteRepository->syncConfigurations($site->id, $request->checks);
                $site = $this->siteRepository->findById($site->id);
            }

            self::clearUserSitesCache($request->user()->id);

            return (new SiteResource($site))->response()->setStatusCode(201);
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
    public function show(Request $request, int $id): SiteResource
    {
        $site = $this->siteRepository->findById($id);

        if (! $site || $site->userId !== $request->user()->id) {
            abort(404);
        }

        return new SiteResource($site);
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
    public function update(UpdateSiteRequest $request, int $id): SiteResource
    {
        $site = $this->siteRepository->findById($id);

        if (! $site || $site->userId !== $request->user()->id) {
            abort(404);
        }

        return DB::transaction(function () use ($request, $id) {
            $site = $this->siteRepository->update($id, $request->safe()->except('checks'));

            if ($request->has('checks')) {
                $this->siteRepository->syncConfigurations($id, $request->checks);
                // Reload site to get updated configurations
                $site = $this->siteRepository->findById($id);
            }

            self::clearUserSitesCache($request->user()->id);

            return new SiteResource($site);
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
    public function destroy(Request $request, int $id): Response
    {
        $site = $this->siteRepository->findById($id);

        if (! $site || $site->userId !== $request->user()->id) {
            abort(404);
        }

        $userId = $site->userId;

        $this->siteRepository->delete($id);

        self::clearUserSitesCache($userId);

        return response()->noContent();
    }

    /**
     * Clear the site cache for a specific user.
     */
    public static function clearUserSitesCache(int $userId): void
    {
        $version = self::CACHE_VERSION;
        Cache::forget("user_sites_{$version}:{$userId}");
    }
}
