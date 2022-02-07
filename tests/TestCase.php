<?php

namespace Ringierimu\LaravelAwsTimestream\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Ringierimu\LaravelAwsTimestream\Tests\Fixtures\Traits\ConfigurableTrait;
use Ringierimu\LaravelAwsTimestream\TimestreamServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use ConfigurableTrait;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('timestream', $this->getTimestreamConfig());
        parent::getEnvironmentSetUp($app);
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
