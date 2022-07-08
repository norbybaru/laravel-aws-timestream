<?php

namespace NorbyBaru\AwsTimestream\Tests\Fixtures\Traits;

trait ConfigurableTrait
{
    public function getTimestreamConfig(): array
    {
        return [
            'key' => env('AWS_TIMESTREAM_KEY'),
            'secret' => env('AWS_TIMESTREAM_SECRET'),
            'database' => 'test-db',
            'tables' => [
                'aliases' => [
                    'test' => 'default',
                ],
            ],
        ];
    }
}
