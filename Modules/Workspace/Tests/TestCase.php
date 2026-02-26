<?php

namespace Modules\Workspace\Tests;

use Tests\TestCase as BaseTestCase;

/**
 * Base TestCase for the Workspace module.
 *
 * This abstract class extends the application's root TestCase
 * and acts as the foundation for all Workspace module tests.
 *
 * Responsibilities:
 *  - Provides a centralized testing entry point for the module.
 *  - Keeps Workspace tests logically separated from other modules.
 *  - Allows future extension with module-specific helpers,
 *    traits (e.g., RefreshDatabase), or custom setup logic.
 *
 * This structure supports a modular architecture and
 * improves long-term maintainability and scalability.
 */
abstract class TestCase extends BaseTestCase
{
    // Shared Workspace testing utilities can be added here when needed.
}
