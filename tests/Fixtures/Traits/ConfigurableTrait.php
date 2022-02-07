<?php

namespace Ringierimu\LaravelAwsTimestream\Tests\Fixtures\Traits;

trait ConfigurableTrait
{
    public function getTimestreamConfig(): array
    {
        return [
            'database' => 'test-db',
            'tables' => [
                'default' => 'default',
                'sources' => [
                    'test' => 'default'
                ],
            ],
        ];
    }
}