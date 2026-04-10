<?php

namespace NorbyBaru\AwsTimestream\Tests\Unit;

use Aws\Result;
use Aws\TimestreamQuery\TimestreamQueryClient;
use Aws\TimestreamWrite\TimestreamWriteClient;
use Mockery;
use NorbyBaru\AwsTimestream\Dto\TimestreamReaderDto;
use NorbyBaru\AwsTimestream\Exception\UnknownTimestreamDataTypeException;
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

    public function test_it_should_throw_exception_for_unknown_type()
    {
        $this->expectException(UnknownTimestreamDataTypeException::class);
        $this->expectExceptionMessage('Unknown Data Type From TimeStream: INVALID_TYPE');

        $this->invokeProtectedMethod($this->service, 'dataType', ['INVALID_TYPE', 'some-value']);
    }

    public function test_it_should_parse_row_correctly()
    {
        // Prepare test data with multiple data types
        $row = [
            'Data' => [
                ['ScalarValue' => '12345'],
                ['ScalarValue' => 'test-value'],
                ['ScalarValue' => '1'],
                ['ScalarValue' => '123.456'],
                ['ScalarValue' => '2024-03-15 10:30:45.123456000'],
            ],
        ];

        $columnInfo = [
            ['Name' => 'user_id', 'Type' => ['ScalarType' => 'BIGINT']],
            ['Name' => 'username', 'Type' => ['ScalarType' => 'VARCHAR']],
            ['Name' => 'is_active', 'Type' => ['ScalarType' => 'BOOLEAN']],
            ['Name' => 'score', 'Type' => ['ScalarType' => 'DOUBLE']],
            ['Name' => 'created_at', 'Type' => ['ScalarType' => 'TIMESTAMP']],
        ];

        $result = $this->invokeProtectedMethod($this->service, 'parseRow', [$row, $columnInfo]);

        // Assert the row is parsed correctly with proper data types
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('is_active', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('created_at', $result);

        $this->assertIsInt($result['user_id']);
        $this->assertEquals(12345, $result['user_id']);

        $this->assertIsString($result['username']);
        $this->assertEquals('test-value', $result['username']);

        $this->assertIsBool($result['is_active']);
        $this->assertTrue($result['is_active']);

        $this->assertIsFloat($result['score']);
        $this->assertEquals(123.456, $result['score']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $result['created_at']);
        $this->assertEquals('2024-03-15 10:30:45', $result['created_at']->format('Y-m-d H:i:s'));
    }

    public function test_it_should_deduplicate_row_keys()
    {
        // Prepare test data with duplicate column names (with :: suffix)
        $row = [
            'Data' => [
                ['ScalarValue' => 'first-value'],
                ['ScalarValue' => 'second-value'],
                ['ScalarValue' => '100'],
            ],
        ];

        $columnInfo = [
            ['Name' => 'username::1', 'Type' => ['ScalarType' => 'VARCHAR']],
            ['Name' => 'username::2', 'Type' => ['ScalarType' => 'VARCHAR']],
            ['Name' => 'user_id', 'Type' => ['ScalarType' => 'BIGINT']],
        ];

        $result = $this->invokeProtectedMethod($this->service, 'parseRow', [$row, $columnInfo]);

        // Assert the row keys are deduplicated (:: suffix removed)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('user_id', $result);

        // The first value should be kept when deduplicating (parseRow skips subsequent duplicates)
        $this->assertEquals('first-value', $result['username']);
        $this->assertEquals(100, $result['user_id']);

        // Verify we only have 2 keys (username and user_id), not 3
        $this->assertCount(2, $result);
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
