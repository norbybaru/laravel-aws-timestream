<?php

namespace NorbyBaru\AwsTimestream\Tests\Fixtures\Traits;

trait ConfigurableTrait
{
    public function getTimestreamConfig(): array
    {
        return [
            'key' => env('AWS_TIMESTREAM_KEY'),
            'secret' => env('AWS_TIMESTREAM_SECRET'),
            'database' => env('AWS_TIMESTREAM_DATABASE', 'laravel-aws-timestream'),
            'tables' => [
                'aliases' => [
                    'test' => 'test',
                ],
            ],
        ];
    }
}
