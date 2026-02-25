<?php

namespace Modules\Users\Application\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * Class CreateUserDTO
 *
 * Data Transfer Object responsible for carrying user creation data
 * from the Presentation layer (HTTP Request, CLI, etc.)
 * into the Application layer.
 *
 * This DTO ensures:
 * - Strong typing
 * - Immutable-like structured data transport
 * - Clear separation between request payload and domain logic
 *
 * ⚠ This class must not contain business logic.
 * It is strictly a data carrier.
 *
 * @package Modules\Users\Application\DTOs
 */
class CreateUserDTO extends DataTransferObject
{
    /**
     * Full name of the user.
     *
     * @var string
     */
    public string $name;

    /**
     * Email address of the user.
     * Must be unique in the system.
     *
     * @var string
     */
    public string $email;

    /**
     * Plain password provided during registration.
     *
     * Note:
     * Password hashing must be handled in the Application service
     * or Domain layer — never inside the DTO.
     *
     * @var string
     */
    public string $password;

    /**
     * Indicates whether the user has administrative privileges.
     *
     * Defaults to false when not explicitly provided.
     *
     * @var bool|null
     */
    public ?bool $is_admin = false;

    /**
     * Create DTO instance from validated request data.
     *
     * This factory method provides a controlled and explicit way
     * to construct the DTO from external input (e.g. Controller).
     *
     * Expected array structure:
     * [
     *     'name' => string,
     *     'email' => string,
     *     'password' => string,
     *     'is_admin' => bool|null (optional)
     * ]
     *
     * @param array<string, mixed> $data Validated request payload
     * @return self
     */
    public static function fromRequest(array $data): self
    {
        return new self([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_admin' => $data['is_admin'] ?? false,
        ]);
    }
}
