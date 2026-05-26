<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Monitoring\Contracts\CachePortInterface;
use App\Domain\Monitoring\Contracts\SiteManagementRepositoryInterface;
use App\Domain\Monitoring\Data\CreateSiteData;
use App\Domain\Monitoring\Models\Site;
use App\Domain\Monitoring\UseCases\CreateSiteUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Sites\StoreSiteRequest;
use App\Http\Requests\Api\Sites\UpdateSiteRequest;
use App\Http\Resources\SiteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class SiteController extends Controller
{
    public function __construct(
        private readonly SiteManagementRepositoryInterface $siteRepository,
        private readonly CreateSiteUseCase $createSiteUseCase,
        private readonly CachePortInterface $cachePort,
    ) {}

    #[OA\Get(
        path: '/api/v1/sites',
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
        $projectId = $request->integer('project_id') ?: null;
        $perPage = min($request->integer('per_page', 10), 50);
        $page = max(1, $request->integer('page', 1));

        $version = CachePortInterface::SITES_CACHE_VERSION;
        $cacheKey = "user_sites_{$version}:{$userId}".($projectId ? ":project_{$projectId}" : '');

        $sitesData = Cache::remember($cacheKey, 60, function () use ($userId, $projectId) {
            $sites = $this->siteRepository->findByUser($userId, $projectId);

            return array_map(static fn (Site $site) => $site->toArray(), $sites);
        });

        $statusCounts = array_column($sitesData, 'status')
                |> array_count_values(...)
                |> (static fn($x) => array_merge(['up' => 0, 'down' => 0, 'slow' => 0, 'pending' => 0], $x));

        $pageData = array_slice($sitesData, ($page - 1) * $perPage, $perPage);
        $pageItems = array_map(fn (array $data) => $this->siteRepository->fromArray($data), $pageData);

        $paginator = new LengthAwarePaginator(
            items: $pageItems,
            total: count($sitesData),
            perPage: $perPage,
            currentPage: $page,
            options: ['path' => $request->url(), 'query' => $request->query()],
        );

        return SiteResource::collection($paginator)->additional([
            'meta' => ['status_counts' => $statusCounts],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/sites',
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
                projectId: $validated['project_id'] ?? null,
            );

            $site = $this->createSiteUseCase->execute(
                $dto,
                $request->user(),
                $validated['checks'] ?? []
            );

            return new SiteResource($site)
                ->response()
                ->setStatusCode(ResponseAlias::HTTP_CREATED);
        });
    }

    #[OA\Get(
        path: '/api/v1/sites/{site}',
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
        path: '/api/v1/sites/{site}',
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

        $checks = null;
        if ($request->has('checks')) {
            $existingByType = array_column($site->configurations, 'id', 'checkTypeId');
            $checks = array_map(static function (array $check) use ($existingByType) {
                $check['id'] ??= $existingByType[$check['check_type_id']] ?? null;

                return $check;
            }, $request->checks);
        }

        return DB::transaction(function () use ($request, $id, $checks) {
            $site = $this->siteRepository->update($id, $request->safe()->except('checks'));

            if ($checks !== null) {
                $this->siteRepository->syncConfigurations($id, $checks);
                // Reload site to get updated configurations
                $site = $this->siteRepository->findById($id);
            }

            $this->cachePort->clearUserSitesCache($request->user()->id);

            return new SiteResource($site);
        });
    }

    #[OA\Delete(
        path: '/api/v1/sites/{site}',
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

        $this->cachePort->clearUserSitesCache($userId);

        return response()->noContent();
    }
}
