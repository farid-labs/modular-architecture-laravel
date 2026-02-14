<?php

namespace Modules\Users\Presentation\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\Domain\Entities\UserEntity;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int|null,
     *     name: string|null,
     *     email: string|null,
     *     email_verified_at: string|null,
     *     is_active: bool,
     *     created_at: string|null,
     *     updated_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $resource = $this->resource;

        if ($resource instanceof UserEntity) {
            return [
                'id' => $resource->getId(),
                'name' => $resource->getFullName(),
                'email' => $resource->getEmail()->getValue(),
                'email_verified_at' => $resource->getEmailVerifiedAt()?->toIso8601String(),
                'is_active' => $resource->isActive(),
                'created_at' => $resource->getCreatedAt()?->toIso8601String(),
                'updated_at' => $resource->getUpdatedAt()?->toIso8601String(),
            ];
        }

        // Fallback for arrays (if any legacy use)
        $data = (array) $resource;

        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'email_verified_at' => isset($data['email_verified_at'])
                ? Carbon::parse($data['email_verified_at'])->toIso8601String()
                : null,
            'is_active' => ! empty($data['email_verified_at']),
            'created_at' => isset($data['created_at'])
                ? Carbon::parse($data['created_at'])->toIso8601String()
                : null,
            'updated_at' => isset($data['updated_at'])
                ? Carbon::parse($data['updated_at'])->toIso8601String()
                : null,
        ];
    }
}
