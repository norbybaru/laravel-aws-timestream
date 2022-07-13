<?php

namespace NorbyBaru\AwsTimestream\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use NorbyBaru\AwsTimestream\Tests\Fixtures\Traits\ConfigurableTrait;
use NorbyBaru\AwsTimestream\TimestreamServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

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
