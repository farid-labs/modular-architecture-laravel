<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        title: 'Modular Architecture Laravel API',
        version: '1.0.0',
        description: 'Production-grade modular monolith with DDD and Clean Architecture',
        contact: new OA\Contact(email: 'admin@faridlabs.com'),
        license: new OA\License(name: 'MIT')
    ),
    servers: [
        new OA\Server(url: 'http://localhost:8080/api/v1', description: 'Local Development'),
        new OA\Server(url: 'https://api.yourdomain.com/api/v1', description: 'Production'),
    ],
    components: new OA\Components(
        securitySchemes: [
            new OA\SecurityScheme(
                securityScheme: 'bearerAuth',
                type: 'http',
                scheme: 'bearer',
                bearerFormat: 'JWT',
                description: 'Enter token without Bearer prefix'
            ),
        ],
        schemas: [
            // Core Error Response
            new OA\Schema(
                schema: 'ErrorResponse',
                type: 'object',
                properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'errors', type: 'object', nullable: true),
                ]
            ),
            // User Resource Schema
            new OA\Schema(
                schema: 'User',
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                    new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                ]
            ),
            // User Create Request
            new OA\Schema(
                schema: 'UserStoreRequest',
                type: 'object',
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'securepassword123'),
                ]
            ),
            // User Update Request
            new OA\Schema(
                schema: 'UserUpdateRequest',
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'John Updated'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'updated@example.com'),
                ]
            ),
        ]
    )
)]
#[OA\Tag(name: 'Authentication', description: 'User authentication endpoints')]
#[OA\Tag(name: 'Users', description: 'User management endpoints')]
#[OA\Tag(name: 'Workspaces', description: 'Workspace management endpoints')]
#[OA\Tag(name: 'Projects', description: 'Project management within workspaces')]
#[OA\Tag(name: 'Tasks', description: 'Task management within projects')]
class ApiDocumentationController extends Controller
{
    // This class exists only for OpenAPI annotations - no methods needed
}
