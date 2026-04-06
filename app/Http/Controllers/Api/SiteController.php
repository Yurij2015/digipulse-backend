<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Sites\StoreSiteRequest;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

use OpenApi\Attributes as OA;

class SiteController extends Controller
{
    #[OA\Get(
        path: '/api/sites',
        summary: 'List user sites',
        tags: ['Sites'],
        security: [['frontendKey' => []], ['bearerAuth' => []]],
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
        return SiteResource::collection(
            $request->user()->sites()->latest()->get()
        );
    }

    #[OA\Post(
        path: '/api/sites',
        summary: 'Store a new site',
        description: 'Creates a new site for monitoring. You can optionally pass an array of checks to be configured for this site.',
        tags: ['Sites'],
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreSiteRequest')
        ),
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
    public function store(StoreSiteRequest $request): SiteResource
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $site = $request->user()->sites()->create($request->safe()->except('checks'));

            if ($request->has('checks')) {
                foreach ($request->checks as $check) {
                    $site->configurations()->create([
                        'check_type_id' => $check['check_type_id'],
                        'params' => $check['params'] ?? null,
                    ]);
                }
            }

            return new SiteResource($site->load('configurations.checkType'));
        });
    }
}
