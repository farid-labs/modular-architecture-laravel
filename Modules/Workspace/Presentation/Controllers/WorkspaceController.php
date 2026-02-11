<?php

namespace Modules\Workspace\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Workspace\Application\DTOs\WorkspaceDTO;
use Modules\Workspace\Application\Services\WorkspaceService;
use Modules\Workspace\Presentation\Requests\StoreWorkspaceRequest;
use Modules\Workspace\Presentation\Requests\UpdateWorkspaceRequest;
use Modules\Workspace\Presentation\Resources\WorkspaceResource;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class WorkspaceController extends Controller
{
    public function __construct(private WorkspaceService $workspaceService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }
        $workspaces = $this->workspaceService->getWorkspacesByUser($user->id);

        return response()->json([
            'data' => WorkspaceResource::collection($workspaces),
            'message' => 'Workspaces retrieved successfully',
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $workspace = $this->workspaceService->getWorkspaceBySlug($slug);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
            'message' => 'Workspace retrieved successfully',
        ]);
    }

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspaceDTO = WorkspaceDTO::fromArray($request->validated());
        $user = $request->user();
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        $workspace = $this->workspaceService->createWorkspace($workspaceDTO, $user);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
            'message' => 'Workspace created successfully',
        ], 201);
    }

    public function update(UpdateWorkspaceRequest $request, int $id): JsonResponse
    {
        $workspaceDTO = WorkspaceDTO::fromArray($request->validated());
        $workspace = $this->workspaceService->updateWorkspace($id, $workspaceDTO);

        return response()->json([
            'data' => new WorkspaceResource($workspace),
            'message' => 'Workspace updated successfully',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->workspaceService->deleteWorkspace($id);

        return response()->json([
            'message' => 'Workspace deleted successfully',
        ]);
    }

    public function addMember(Request $request, int $workspaceId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:owner,admin,member',
        ]);

        $this->workspaceService->addUserToWorkspace(
            $workspaceId,
            $request->user_id,
            $request->role
        );

        return response()->json([
            'message' => 'Member added successfully',
        ]);
    }

    public function removeMember(Request $request, int $workspaceId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $this->workspaceService->removeUserFromWorkspace(
            $workspaceId,
            $request->user_id
        );

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }
}
