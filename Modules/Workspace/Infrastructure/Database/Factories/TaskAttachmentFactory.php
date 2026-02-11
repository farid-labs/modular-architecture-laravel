<?php

namespace Modules\Workspace\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Workspace\Infrastructure\Persistence\Models\TaskAttachment;
use Modules\Workspace\Infrastructure\Persistence\Models\Task;
use Modules\Users\Infrastructure\Persistence\Models\User;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TaskAttachment>
 */
class TaskAttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Modules\Workspace\Infrastructure\Persistence\Models\TaskAttachment>
     */
    protected $model = TaskAttachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Simulate a file type
        $fileTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp', // images
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' // docs
        ];

        $fileType = $this->faker->randomElement($fileTypes);
        $extension = match ($fileType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            default => 'bin'
        };

        $fileName = $this->faker->word() . '.' . $extension;

        return [
            'task_id' => Task::factory(),
            'file_name' => $fileName,
            'file_path' => 'attachments/' . $fileName,
            'file_size' => $this->faker->numberBetween(1024, 1024 * 1024), // 1KB - 1MB
            'file_type' => $fileType,
            'uploaded_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the attachment is an image.
     */
    public function image(): static
    {
        return $this->state(fn() => [
            'file_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'image/gif', 'image/webp']),
            'file_name' => $this->faker->word() . '.' . $this->faker->fileExtension(),
        ]);
    }

    /**
     * Indicate that the attachment is a document.
     */
    public function document(): static
    {
        return $this->state(fn() => [
            'file_type' => $this->faker->randomElement([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ]),
            'file_name' => $this->faker->word() . '.' . $this->faker->fileExtension(),
        ]);
    }
}
