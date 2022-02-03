<?php

namespace Ringierimu\LaravelAwsTimestream;

use Aws\Credentials\Credentials;
use Illuminate\Support\ServiceProvider;
use Aws\TimestreamQuery\TimestreamQueryClient;
use Aws\TimestreamWrite\TimestreamWriteClient;

class TimestreamServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishConfig();
    }

    public function register()
    {
        $this->mergeConfigFrom($this->configPath(), 'timestream');

        $this->app->singleton(TimestreamQueryClient::class, function ($app) {
            return new TimestreamQueryClient([
                'version' => 'latest',
                'region' => 'eu-west-1',
                'credentials' => new Credentials(
                    config('timestream.aws-timestream.key'),
                    config('timestream.aws-timestream.secret')
                ),
            ]);
        });

        $this->app->singleton(TimestreamWriteClient::class, function ($app) {
            return new TimestreamWriteClient([
                'version' => 'latest',
                'region' => 'eu-west-1',
                'credentials' => new Credentials(
                    config('timestream.aws-timestream.key'),
                    config('timestream.aws-timestream.secret')
                ),
            ]);
        });
    }

    /**
     * Return config file.
     *
     * @return string
     */
    protected function configPath()
    {
        return __DIR__ . '/../config/timestream.php';
    }

    /**
     * Publish config file.
     */
    protected function publishConfig()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath() => config_path('timestream.php'),
            ], 'timestream-config');
        }
    }

}