<?php

namespace Modules\Users\Tests;

use Tests\TestCase as BaseTestCase;

/**
 * Base TestCase for the Users module.
 *
 * This abstract class extends the application's root TestCase
 * and serves as the foundation for all Users module tests.
 *
 * Purpose:
 *  - Provides a centralized place for shared testing logic.
 *  - Keeps Users module tests isolated from other modules.
 *  - Allows future extension with module-specific helpers,
 *    traits, or setup configuration.
 */
abstract class TestCase extends BaseTestCase
{
    // Add shared Users module testing utilities here when needed.
}
