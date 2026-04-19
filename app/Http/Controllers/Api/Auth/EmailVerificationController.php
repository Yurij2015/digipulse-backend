<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EmailVerificationController extends Controller
{
    #[OA\Post(
        path: '/api/email/verification-notification',
        operationId: 'sendVerificationNotification',
        description: 'Send a verification email to the authenticated user',
        summary: 'Send email verification notification',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification link sent',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Verification link sent.'),
                    ]
                )
            ),
            new OA\Response(
                response: 202,
                description: 'Accepted (Verification link sent)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Verification link sent.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function sendVerificationNotification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.'], 202);
    }

    #[OA\Post(
        path: '/api/email/verify',
        summary: 'Verify email address',
        description: 'Verify user email using POST request with signature',
        operationId: 'verifyEmail',
        tags: ['Auth'],
        security: [['frontendKey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id', 'hash', 'signature'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'hash', type: 'string', example: 'abc123...'),
                    new OA\Property(property: 'signature', type: 'string', example: 'xyz789...'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email verified successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Email verified successfully.'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Invalid verification link or signature'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'hash' => 'required|string',
            'signature' => 'required|string',
        ]);

        /** @var User $user */
        $user = User::findOrFail($request->id);

        if (! hash_equals((string) $request->hash, sha1($user->email))) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        $baseUrl = rtrim(config('app.frontend_url'), '/');
        $expectedUrl = $baseUrl.'/auth/verify-email?'.http_build_query([
            'id' => $request->id,
            'hash' => $request->hash,
        ]);

        $expectedSignature = hash_hmac('sha256', $expectedUrl, config('app.key'));
        if (! hash_equals($expectedSignature, $request->signature)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json(['message' => 'Email verified successfully.']);
    }
}
