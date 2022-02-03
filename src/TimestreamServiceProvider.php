<?php

namespace Ringierimu\LaravelAwsTimestream;

use Illuminate\Support\ServiceProvider;

class TimestreamServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishConfig();
    }

    public function register()
    {
        $this->mergeConfigFrom($this->configPath(), 'timestream');

        $this->app->singleton(TimestreamManager::class, function ($app) {
            return new TimestreamManager(
                config('timestream.key'),
                config('timestream.secret'),
                config('timestream.profile'),
            );
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
