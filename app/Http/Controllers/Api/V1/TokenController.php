<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tokens\CreateTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TokenController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tokens',
        operationId: 'listTokens',
        summary: 'List MCP tokens',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Tokens'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of MCP tokens',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'tokens',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'last_used_at', type: 'string', format: 'date-time', nullable: true),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()
            ->tokens()
            ->where('name', '!=', 'auth_token')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'created_at', 'last_used_at']);

        return response()->json(['tokens' => $tokens]);
    }

    #[OA\Post(
        path: '/api/v1/tokens',
        operationId: 'createToken',
        summary: 'Create MCP token',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 64, example: 'My MCP token'),
                ]
            )
        ),
        tags: ['Tokens'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Token created. The plain-text token is returned only once.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'mcp_url', type: 'string', example: 'https://api.example.com/mcp?token=1|abc...'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(CreateTokenRequest $request): JsonResponse
    {
        $newToken = $request->user()->createToken($request->name, ['mcp']);

        $base = rtrim(config('app.mcp_server_url') ?: config('app.url'), '/');
        $mcpUrl = $base.'/mcp?token='.$newToken->plainTextToken;

        return response()->json([
            'id' => $newToken->accessToken->id,
            'name' => $newToken->accessToken->name,
            'mcp_url' => $mcpUrl,
            'created_at' => $newToken->accessToken->created_at,
        ], 201);
    }

    #[OA\Delete(
        path: '/api/v1/tokens/{id}',
        operationId: 'deleteToken',
        summary: 'Revoke MCP token',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Tokens'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Token revoked'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Token not found'),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $request->user()
            ->tokens()
            ->where('id', $id)
            ->where('name', '!=', 'auth_token')
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Token not found'], 404);
        }

        return response()->json(null, 204);
    }
}
