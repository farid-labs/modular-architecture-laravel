<?php

namespace Modules\Workspace\Application\DTOs;

use Illuminate\Support\Str;
use Spatie\DataTransferObject\DataTransferObject;

class WorkspaceDTO extends DataTransferObject
{
    public ?string $name = null;

    public ?string $slug = null;

    public ?string $description = null;

    public ?int $owner_id = null;

    public string $status = 'active';

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $filteredData = array_filter($data, fn ($value) => $value !== null);

        return new self($filteredData);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug
                ? Str::slug($this->slug)
                : ($this->name ? Str::slug($this->name) : null),
            'description' => $this->description,
            'owner_id' => $this->owner_id,
            'status' => $this->status,
        ];
    }
}
