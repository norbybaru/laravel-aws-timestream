<?php

namespace Ringierimu\LaravelAwsTimestream\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Ringierimu\LaravelAwsTimestream\TimestreamServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            TimestreamServiceProvider::class,
        ];
    }
}
