<?php

namespace Modules\Workspace\Application\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

class WorkspaceDTO extends DataTransferObject
{
    public string $name;

    public ?string $description = null;

    public ?int $owner_id = null;

    public ?string $status = 'active';

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->generateSlug(),
            'description' => $this->description,
            'owner_id' => $this->owner_id,
            'status' => $this->status,
        ];
    }

    private function generateSlug(): string
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $this->name);

        if ($slug === null) {
            // Extremely rare, but safe fallback
            throw new \RuntimeException('Slug generation failed.');
        }

        return strtolower(trim($slug, '-'));
    }
}
