<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Monitoring\Contracts\ProjectRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository
    ) {}

    #[OA\Get(
        path: '/api/v1/projects',
        summary: 'List user projects',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Projects'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Project')
                )
            ),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $this->projectRepository->findByUser($request->user()->id);

        return ProjectResource::collection($projects);
    }

    #[OA\Post(
        path: '/api/v1/projects',
        summary: 'Create a new project',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Client Alpha'),
                    new OA\Property(property: 'description', type: 'string', example: 'All sites for Client Alpha', nullable: true),
                ]
            )
        ),
        tags: ['Projects'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Project')
            ),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $project = $this->projectRepository->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/projects/{project}',
        summary: 'Get project details',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/Project')
            ),
        ]
    )]
    public function show(Request $request, int $id): ProjectResource
    {
        $project = $this->projectRepository->findById($id);

        if (! $project || $project->userId !== $request->user()->id) {
            abort(404);
        }

        return new ProjectResource($project);
    }

    #[OA\Put(
        path: '/api/v1/projects/{project}',
        summary: 'Update project',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Updated Name'),
                    new OA\Property(property: 'description', type: 'string', example: 'Updated Description', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project updated successfully'
            ),
        ]
    )]
    public function update(Request $request, int $id): ProjectResource
    {
        $project = $this->projectRepository->update($id, $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]));

        return new ProjectResource($project);
    }

    #[OA\Delete(
        path: '/api/v1/projects/{project}',
        summary: 'Delete project',
        security: [['frontendKey' => []], ['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(name: 'project', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Project deleted successfully'
            ),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $project = $this->projectRepository->findById($id);

        if (! $project || $project->userId !== $request->user()->id) {
            abort(404);
        }

        $this->projectRepository->delete($id);

        return response()->json(null, 204);
    }
}
