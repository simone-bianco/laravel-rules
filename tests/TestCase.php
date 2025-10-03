<?php

namespace SimoneBianco\LaravelRules\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SimoneBianco\LaravelRules\LaravelRulesServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelRulesServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default configuration
        $app['config']->set('laravel-rules.rules_path', 'tests/fixtures/rules');
        $app['config']->set('laravel-rules.cache_enabled', true);
        $app['config']->set('laravel-rules.cache_ttl', 3600);
    }
}

