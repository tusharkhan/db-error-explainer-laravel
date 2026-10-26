<?php

namespace TusharKhan\DbErrorExplainer\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelIgnition\IgnitionServiceProvider;
use TusharKhan\DbErrorExplainer\Laravel\ExplainerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [IgnitionServiceProvider::class, ExplainerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
    }
}
