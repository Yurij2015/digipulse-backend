<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CheckTypeResource;
use App\Models\CheckType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class CheckTypeController extends Controller
{
    #[OA\Get(
        path: '/api/v1/check-types',
        summary: 'List available check types',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Check Types'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/CheckTypeSchema')
                )
            ),
        ]
    )]
    /**
     * Display a listing of available check types.
     */
    public function index(): AnonymousResourceCollection
    {
        return CheckTypeResource::collection(
            CheckType::where('is_active', true)->get()
        );
    }
}
