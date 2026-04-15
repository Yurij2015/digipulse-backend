<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    #[OA\Post(
        path: '/api/register',
        summary: 'Register a new user',
        tags: ['Auth'],
        security: [['frontendKey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserSchema'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abcd1234...'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed'
            ),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $userData = $this->authService->register($request->validated());

        return response()->json($userData, 201);
    }

    #[OA\Post(
        path: '/api/login',
        summary: 'Login user',
        description: 'Authenticate a user and return a token',
        operationId: 'loginUser',
        tags: ['Auth'],
        security: [['frontendKey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful login',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $userData = $this->authService->login($request->email, $request->password);

        if (! $userData) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        return response()->json($userData);
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout user',
        description: 'Revoke the authenticated user\'s tokens',
        operationId: 'logoutUser',
        tags: ['Auth'],
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful logout',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully logged out'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ]
    )]
    public function logout(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth()->user();
        $user?->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }

    #[OA\Get(
        path: '/api/me',
        operationId: 'getMe',
        summary: 'Get authenticated user',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user',
                content: new OA\JsonContent(ref: '#/components/schemas/UserSchema')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json(new UserResource($user));
    }
}
