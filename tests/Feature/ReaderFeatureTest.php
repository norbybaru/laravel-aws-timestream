<?php

namespace NorbyBaru\AwsTimestream\Tests\Feature;

use NorbyBaru\AwsTimestream\Dto\TimestreamReaderDto;
use NorbyBaru\AwsTimestream\Tests\TestCase;
use NorbyBaru\AwsTimestream\TimestreamBuilder;
use NorbyBaru\AwsTimestream\TimestreamService;

class ReaderFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Skip feature tests if AWS credentials are not configured or connection fails
        if (!getenv('AWS_ACCESS_KEY_ID') || !getenv('AWS_SECRET_ACCESS_KEY')) {
            $this->markTestSkipped('AWS credentials not configured. Set AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY to run feature tests.');
        }
    }

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
        $max = 300;
        $queryBuilder = TimestreamBuilder::query()
            ->select('*')
            ->from(config('timestream.database'), 'test')
            ->orderBy('time', 'desc')
            ->limitBy($max);

        $reader = TimestreamReaderDto::make($queryBuilder);
        /** @var TimestreamService */
        $timestreamService = app(TimestreamService::class);
        $result = $timestreamService->query($reader);

        $this->assertEquals($max, $result->count());
    }
}
