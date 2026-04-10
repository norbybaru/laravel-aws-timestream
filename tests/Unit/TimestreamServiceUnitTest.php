<?php

namespace NorbyBaru\AwsTimestream\Tests\Unit;

use Aws\Result;
use Aws\TimestreamQuery\TimestreamQueryClient;
use Aws\TimestreamWrite\TimestreamWriteClient;
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
    protected TimestreamWriteClient $mockWriteClient;
    protected TimestreamManager $mockManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockQueryClient = Mockery::mock(TimestreamQueryClient::class);
        $this->mockWriteClient = Mockery::mock(TimestreamWriteClient::class);
        $this->mockManager = Mockery::mock(TimestreamManager::class);
        $this->mockManager->shouldReceive('getReader')->andReturn($this->mockQueryClient);
        $this->mockManager->shouldReceive('getWriter')->andReturn($this->mockWriteClient);

        $this->service = new TimestreamService($this->mockManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_should_convert_data_types_correctly()
    {
        // Test BIGINT conversion
        $bigintResult = $this->invokeProtectedMethod($this->service, 'dataType', ['BIGINT', '12345']);
        $this->assertIsInt($bigintResult);
        $this->assertEquals(12345, $bigintResult);

        // Test BOOLEAN conversion
        $booleanTrueResult = $this->invokeProtectedMethod($this->service, 'dataType', ['BOOLEAN', '1']);
        $this->assertIsBool($booleanTrueResult);
        $this->assertTrue($booleanTrueResult);

        $booleanFalseResult = $this->invokeProtectedMethod($this->service, 'dataType', ['BOOLEAN', '0']);
        $this->assertIsBool($booleanFalseResult);
        $this->assertFalse($booleanFalseResult);

        // Test VARCHAR conversion
        $varcharResult = $this->invokeProtectedMethod($this->service, 'dataType', ['VARCHAR', 'test-string']);
        $this->assertIsString($varcharResult);
        $this->assertEquals('test-string', $varcharResult);

        // Test DOUBLE conversion
        $doubleResult = $this->invokeProtectedMethod($this->service, 'dataType', ['DOUBLE', '123.456']);
        $this->assertIsFloat($doubleResult);
        $this->assertEquals(123.456, $doubleResult);
    }

    public function test_it_should_parse_timestamp_correctly()
    {
        // Test TIMESTAMP conversion with format 'Y-m-d H:i:s.u000'
        $timestampInput = '2024-03-15 10:30:45.123456000';
        $timestampResult = $this->invokeProtectedMethod($this->service, 'dataType', ['TIMESTAMP', $timestampInput]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $timestampResult);
        $this->assertEquals('2024-03-15 10:30:45', $timestampResult->format('Y-m-d H:i:s'));
    }

    public function test_it_should_return_null_for_null_values()
    {
        // Test that null values return null regardless of type
        $bigintResult = $this->invokeProtectedMethod($this->service, 'dataType', ['BIGINT', null]);
        $this->assertNull($bigintResult);

        $booleanResult = $this->invokeProtectedMethod($this->service, 'dataType', ['BOOLEAN', null]);
        $this->assertNull($booleanResult);

        $varcharResult = $this->invokeProtectedMethod($this->service, 'dataType', ['VARCHAR', null]);
        $this->assertNull($varcharResult);

        $doubleResult = $this->invokeProtectedMethod($this->service, 'dataType', ['DOUBLE', null]);
        $this->assertNull($doubleResult);

        $timestampResult = $this->invokeProtectedMethod($this->service, 'dataType', ['TIMESTAMP', null]);
        $this->assertNull($timestampResult);
    }

    /**
     * Helper method to invoke protected/private methods for testing
     */
    private function invokeProtectedMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
