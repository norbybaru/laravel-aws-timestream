<?php

namespace Ringierimu\AwsTimestream\Tests\Fixtures\Traits;

trait ConfigurableTrait
{
    public function getTimestreamConfig(): array
    {
        return [
            'database' => 'test-db',
            'tables' => [
                'default' => 'default',
                'sources' => [
                    'test' => 'default',
                ],
            ],
        ];
    }
}
