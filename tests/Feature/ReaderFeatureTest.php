<?php

namespace NorbyBaru\AwsTimestream\Tests\Feature;

use NorbyBaru\AwsTimestream\Dto\TimestreamReaderDto;
use NorbyBaru\AwsTimestream\Tests\TestCase;
use NorbyBaru\AwsTimestream\TimestreamBuilder;
use NorbyBaru\AwsTimestream\TimestreamService;

class ReaderFeatureTest extends TestCase
{
    public function test_it_should_return_results()
    {
        $queryBuilder = TimestreamBuilder::query()
            ->select('*')
            ->from(config('timestream.database'), 'test')
            ->whereAgo('time', '24h', '>=')
            ->orderBy('time', 'desc');

        $reader = TimestreamReaderDto::make($queryBuilder);

        /** @var TimestreamService */
        $timestreamService = app(TimestreamService::class);
        $result = $timestreamService->query($reader);

        $this->assertNotEmpty($result->toArray());
        $this->assertNotEquals(0, $result->count());
    }

    public function test_it_should_return_paginated_results()
    {
        $max = 5000;
        $queryBuilder = TimestreamBuilder::query()
            ->select('*')
            ->from(config('timestream.database'), 'test')
            ->whereAgo('time', '48h', '>=')
            ->orderBy('time', 'desc')
            ->limitBy($max);

        $reader = TimestreamReaderDto::make($queryBuilder);
        /** @var TimestreamService */
        $timestreamService = app(TimestreamService::class);
        $result = $timestreamService->query($reader);

        $this->assertEquals($max, $result->count());
    }
}
