<?php

namespace NorbyBaru\AwsTimestream\Tests\Unit;

use Aws\Result;
use Aws\TimestreamQuery\TimestreamQueryClient;
use Mockery;
use NorbyBaru\AwsTimestream\Dto\TimestreamReaderDto;
use NorbyBaru\AwsTimestream\Tests\TestCase;
use NorbyBaru\AwsTimestream\TimestreamBuilder;
use NorbyBaru\AwsTimestream\TimestreamManager;
use NorbyBaru\AwsTimestream\TimestreamService;

class TimestreamServiceUnitTest extends TestCase
{
    protected TimestreamService $service;
    protected TimestreamQueryClient $mockQueryClient;
    protected TimestreamManager $mockManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockQueryClient = Mockery::mock(TimestreamQueryClient::class);
        $this->mockManager = Mockery::mock(TimestreamManager::class);
        $this->mockManager->shouldReceive('getReader')->andReturn($this->mockQueryClient);
        $this->mockManager->shouldReceive('getWriter')->andReturnNull();

        $this->service = new TimestreamService($this->mockManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
