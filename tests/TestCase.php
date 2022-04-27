<?php

namespace Ringierimu\AwsTimestream\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Ringierimu\AwsTimestream\Tests\Fixtures\Traits\ConfigurableTrait;
use Ringierimu\AwsTimestream\TimestreamServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use ConfigurableTrait;
    use WithFaker;

    protected $loadEnvironmentVariables = true;

    protected function setUp(): void
    {
        $this->loadEnvironmentVariables();
        parent::setUp();
        $this->setUpFaker();
    }

    protected function loadEnvironmentVariables()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
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
