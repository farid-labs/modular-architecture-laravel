<?php

namespace Modules\Notifications\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

/**
 * Base TestCase for the Notifications module.
 *
 * This abstract class extends the application's main TestCase
 * and provides shared configuration for all Notifications module tests.
 *
 * Responsibilities:
 *  - Ensures database state is refreshed between tests.
 *  - Centralizes common testing behavior for the module.
 *  - Keeps module tests isolated and predictable.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Refresh the database after each test.
     *
     * This ensures a clean database state, preventing
     * cross-test data contamination and improving reliability.
     */
    use RefreshDatabase;
}
