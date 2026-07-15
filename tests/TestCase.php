<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->app->environment('testing')) {
            throw new RuntimeException('Tests may only run with APP_ENV=testing.');
        }

        if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'ai_marketing_agents_testing') {
            throw new RuntimeException('Tests require the isolated ai_marketing_agents_testing database.');
        }
    }
}
