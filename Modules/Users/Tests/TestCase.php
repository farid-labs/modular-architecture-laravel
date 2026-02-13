<?php

namespace Modules\Users\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

// use Illuminate\Foundation\Testing\RefreshDatabase;   ← REMOVE or COMMENT OUT

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    // use RefreshDatabase;   ← REMOVE or COMMENT OUT

    protected function setUp(): void
    {
        parent::setUp();
        // Do NOT add any Artisan::call('migrate') calls here
    }
}
