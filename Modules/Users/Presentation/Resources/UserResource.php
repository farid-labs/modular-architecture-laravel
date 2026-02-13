<?php

namespace Modules\Users\Presentation\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\Infrastructure\Persistence\Models\User;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;

        if ($resource instanceof User) {
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'email' => $resource->email,
                'email_verified_at' => $resource->email_verified_at?->toIso8601String(),
                'is_active' => $resource->isActive(),
                'created_at' => $resource->created_at->toIso8601String(),
                'updated_at' => $resource->updated_at->toIso8601String(),
            ];
        }

        // Defensive fallback for arrays (should be rare after controller fix)
        $data = (array) $resource;

        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'email_verified_at' => isset($data['email_verified_at']) ? Carbon::parse($data['email_verified_at'])->toIso8601String() : null,
            'is_active' => ! empty($data['email_verified_at']),
            'created_at' => isset($data['created_at']) ? Carbon::parse($data['created_at'])->toIso8601String() : null,
            'updated_at' => isset($data['updated_at']) ? Carbon::parse($data['updated_at'])->toIso8601String() : null,
        ];
    }
}
