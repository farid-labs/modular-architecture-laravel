<?php

namespace Modules\Users\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\Domain\Entities\UserEntity;
use Modules\Users\Infrastructure\Persistence\Models\UserModel;

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
        if ($this->resource instanceof UserEntity) {
            return [
                'id' => $this->resource->getId(),
                'name' => $this->resource->getName()->getValue(),
                'email' => $this->resource->getEmail()->getValue(),
                'email_verified_at' => $this->resource->getEmailVerifiedAt()?->toIso8601String(),
                'is_active' => $this->resource->isActive(),
                'created_at' => $this->resource->getCreatedAt()->toIso8601String(),
                'updated_at' => $this->resource->getUpdatedAt()->toIso8601String(),
            ];
        }

        if ($this->resource instanceof UserModel) {
            return [
                'id' => $this->resource->id,
                'name' => $this->resource->name,
                'email' => $this->resource->email,
                'email_verified_at' => $this->resource->email_verified_at?->toIso8601String(),
                'is_active' => (bool) $this->resource->getAttribute('is_active'),
                'created_at' => $this->resource->created_at->toIso8601String(),
                'updated_at' => $this->resource->updated_at->toIso8601String(),
            ];
        }

        $data = (array) $this->resource;

        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'email_verified_at' => $data['email_verified_at'] ?? null,
            'is_active' => ! empty($data['email_verified_at']),
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
        ];
    }
}
