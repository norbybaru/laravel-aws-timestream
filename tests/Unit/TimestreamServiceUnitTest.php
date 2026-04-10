<?php

namespace NorbyBaru\AwsTimestream\Tests\Unit;

use Aws\Result;
use Aws\TimestreamQuery\Exception\TimestreamQueryException;
use Aws\TimestreamQuery\TimestreamQueryClient;
use Aws\TimestreamWrite\Exception\TimestreamWriteException;
use Aws\TimestreamWrite\TimestreamWriteClient;
use Mockery;
use NorbyBaru\AwsTimestream\Dto\TimestreamReaderDto;
use NorbyBaru\AwsTimestream\Exception\FailTimestreamQueryException;
use NorbyBaru\AwsTimestream\Exception\FailTimestreamWriterException;
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

    public function test_it_should_handle_successful_write()
    {
        // Prepare test payload
        $payload = [
            'DatabaseName' => 'test_database',
            'TableName' => 'test_table',
            'Records' => [
                [
                    'Time' => '1234567890',
                    'MeasureName' => 'temperature',
                    'MeasureValue' => '25.5',
                    'MeasureValueType' => 'DOUBLE',
                ],
            ],
        ];

        // Prepare mock AWS Result with status 200
        $mockResult = Mockery::mock(Result::class);
        $mockResult->shouldReceive('get')
            ->with('@metadata')
            ->andReturn(['statusCode' => 200]);

        // Mock the writeRecords method to return our mock result
        $this->mockWriteClient
            ->shouldReceive('writeRecords')
            ->once()
            ->with($payload)
            ->andReturn($mockResult);

        // Call the ingest method
        $result = $this->invokeProtectedMethod($this->service, 'ingest', [$payload]);

        // Assert the result is returned correctly
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(200, $result->get('@metadata')['statusCode']);
    }

    public function test_it_should_throw_exception_for_failed_status()
    {
        // Prepare test payload
        $payload = [
            'DatabaseName' => 'test_database',
            'TableName' => 'test_table',
            'Records' => [
                [
                    'Time' => '1234567890',
                    'MeasureName' => 'temperature',
                    'MeasureValue' => '25.5',
                    'MeasureValueType' => 'DOUBLE',
                ],
            ],
        ];

        // Prepare mock AWS Result with non-200 status code
        $mockResult = Mockery::mock(Result::class);
        $mockResult->shouldReceive('get')
            ->with('@metadata')
            ->andReturn(['statusCode' => 500]);

        // Mock the writeRecords method to return our mock result
        $this->mockWriteClient
            ->shouldReceive('writeRecords')
            ->once()
            ->with($payload)
            ->andReturn($mockResult);

        // Expect FailTimestreamWriterException to be thrown
        $this->expectException(FailTimestreamWriterException::class);

        // Call the ingest method
        $this->invokeProtectedMethod($this->service, 'ingest', [$payload]);
    }

    public function test_it_should_handle_rejected_records_exception()
    {
        // Prepare test payload with multiple records
        $payload = [
            'DatabaseName' => 'test_database',
            'TableName' => 'test_table',
            'Records' => [
                [
                    'Time' => '1234567890',
                    'MeasureName' => 'temperature',
                    'MeasureValue' => '25.5',
                    'MeasureValueType' => 'DOUBLE',
                ],
                [
                    'Time' => '1234567891',
                    'MeasureName' => 'humidity',
                    'MeasureValue' => '60',
                    'MeasureValueType' => 'BIGINT',
                ],
                [
                    'Time' => '1234567892',
                    'MeasureName' => 'pressure',
                    'MeasureValue' => '1013.25',
                    'MeasureValueType' => 'DOUBLE',
                ],
            ],
        ];

        // Prepare mock TimestreamWriteException with RejectedRecordsException
        $mockCommand = Mockery::mock(\Aws\CommandInterface::class);
        $mockException = Mockery::mock(TimestreamWriteException::class, ['RejectedRecordsException', $mockCommand]);
        $mockException->shouldReceive('getAwsErrorCode')
            ->andReturn('RejectedRecordsException');
        $mockException->shouldReceive('get')
            ->with('RejectedRecords')
            ->andReturn([
                [
                    'RecordIndex' => 0,
                    'Reason' => 'Invalid time value',
                ],
                [
                    'RecordIndex' => 2,
                    'Reason' => 'Duplicate record',
                ],
            ]);
        $mockException->shouldReceive('getMessage')
            ->andReturn('Records were rejected');
        $mockException->shouldReceive('getCode')
            ->andReturn(0);
        $mockException->shouldReceive('getPrevious')
            ->andReturn(null);

        // Mock the writeRecords method to throw the exception
        $this->mockWriteClient
            ->shouldReceive('writeRecords')
            ->once()
            ->with($payload)
            ->andThrow($mockException);

        try {
            // Call the ingest method - should throw FailTimestreamWriterException
            $this->invokeProtectedMethod($this->service, 'ingest', [$payload]);
            $this->fail('Expected FailTimestreamWriterException was not thrown');
        } catch (FailTimestreamWriterException $e) {
            // Assert the exception context contains mapped rejected records
            $context = $e->context();
            $this->assertIsArray($context);
            $this->assertCount(2, $context);

            // Assert first rejected record mapping
            $this->assertEquals(0, $context[0]['RecordIndex']);
            $this->assertEquals($payload['Records'][0], $context[0]['Record']);
            $this->assertEquals('Invalid time value', $context[0]['Reason']);

            // Assert second rejected record mapping
            $this->assertEquals(2, $context[1]['RecordIndex']);
            $this->assertEquals($payload['Records'][2], $context[1]['Record']);
            $this->assertEquals('Duplicate record', $context[1]['Reason']);
        }
    }

    public function test_it_should_handle_single_page_query()
    {
        // Prepare mock AWS Result without NextToken (single page)
        $mockResult = Mockery::mock(Result::class);
        $mockResult->shouldReceive('get')
            ->with('NextToken')
            ->andReturn(null);

        $mockResult->shouldReceive('get')
            ->with('ColumnInfo')
            ->andReturn([
                ['Name' => 'user_id', 'Type' => ['ScalarType' => 'BIGINT']],
                ['Name' => 'username', 'Type' => ['ScalarType' => 'VARCHAR']],
            ]);

        $mockResult->shouldReceive('get')
            ->with('Rows')
            ->andReturn([
                [
                    'Data' => [
                        ['ScalarValue' => '123'],
                        ['ScalarValue' => 'john_doe'],
                    ],
                ],
                [
                    'Data' => [
                        ['ScalarValue' => '456'],
                        ['ScalarValue' => 'jane_doe'],
                    ],
                ],
            ]);

        $mockResult->shouldReceive('get')
            ->with('QueryStatus')
            ->andReturn(['Status' => 'SUCCESS']);

        // Create a query builder
        $queryBuilder = TimestreamBuilder::query()->from('test_database', 'test_table');

        // Mock the query client to return our mock result
        $this->mockQueryClient
            ->shouldReceive('query')
            ->once()
            ->andReturn($mockResult);

        // Execute the query
        $readerDto = TimestreamReaderDto::make($queryBuilder);
        $result = $this->service->query($readerDto);

        // Assert the result is a Collection
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);

        // Assert we got 2 rows
        $this->assertCount(2, $result);

        // Assert first row data
        $firstRow = $result->first();
        $this->assertIsArray($firstRow);
        $this->assertEquals(123, $firstRow['user_id']);
        $this->assertEquals('john_doe', $firstRow['username']);

        // Assert second row data
        $secondRow = $result->last();
        $this->assertIsArray($secondRow);
        $this->assertEquals(456, $secondRow['user_id']);
        $this->assertEquals('jane_doe', $secondRow['username']);
    }

    public function test_it_should_handle_paginated_query()
    {
        // Prepare mock AWS Result for first page (with NextToken)
        $mockResultPage1 = Mockery::mock(Result::class);
        $mockResultPage1->shouldReceive('get')
            ->with('NextToken')
            ->andReturn('next-token-123');

        $mockResultPage1->shouldReceive('get')
            ->with('ColumnInfo')
            ->andReturn([
                ['Name' => 'user_id', 'Type' => ['ScalarType' => 'BIGINT']],
                ['Name' => 'username', 'Type' => ['ScalarType' => 'VARCHAR']],
            ]);

        $mockResultPage1->shouldReceive('get')
            ->with('Rows')
            ->andReturn([
                [
                    'Data' => [
                        ['ScalarValue' => '100'],
                        ['ScalarValue' => 'alice'],
                    ],
                ],
                [
                    'Data' => [
                        ['ScalarValue' => '200'],
                        ['ScalarValue' => 'bob'],
                    ],
                ],
            ]);

        $mockResultPage1->shouldReceive('get')
            ->with('QueryStatus')
            ->andReturn(['Status' => 'SUCCESS']);

        // Prepare mock AWS Result for second page (without NextToken)
        $mockResultPage2 = Mockery::mock(Result::class);
        $mockResultPage2->shouldReceive('get')
            ->with('NextToken')
            ->andReturn(null);

        $mockResultPage2->shouldReceive('get')
            ->with('ColumnInfo')
            ->andReturn([
                ['Name' => 'user_id', 'Type' => ['ScalarType' => 'BIGINT']],
                ['Name' => 'username', 'Type' => ['ScalarType' => 'VARCHAR']],
            ]);

        $mockResultPage2->shouldReceive('get')
            ->with('Rows')
            ->andReturn([
                [
                    'Data' => [
                        ['ScalarValue' => '300'],
                        ['ScalarValue' => 'charlie'],
                    ],
                ],
            ]);

        $mockResultPage2->shouldReceive('get')
            ->with('QueryStatus')
            ->andReturn(['Status' => 'SUCCESS']);

        // Create a query builder
        $queryBuilder = TimestreamBuilder::query()->from('test_database', 'test_table');

        // Mock the query client to return different results based on NextToken
        $this->mockQueryClient
            ->shouldReceive('query')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return !isset($arg['NextToken']);
            }))
            ->andReturn($mockResultPage1);

        $this->mockQueryClient
            ->shouldReceive('query')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return isset($arg['NextToken']) && $arg['NextToken'] === 'next-token-123';
            }))
            ->andReturn($mockResultPage2);

        // Execute the query
        $readerDto = TimestreamReaderDto::make($queryBuilder);
        $result = $this->service->query($readerDto);

        // Assert the result is a Collection
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);

        // Assert we got all 3 rows from both pages
        $this->assertCount(3, $result);

        // Assert rows are returned in reverse page order (page 2 first, then page 1)
        // due to recursive merge in runQuery method

        // Assert first row data (from page 2 - last page comes first)
        $this->assertIsArray($result[0]);
        $this->assertEquals(300, $result[0]['user_id']);
        $this->assertEquals('charlie', $result[0]['username']);

        // Assert second row data (from page 1)
        $this->assertIsArray($result[1]);
        $this->assertEquals(100, $result[1]['user_id']);
        $this->assertEquals('alice', $result[1]['username']);

        // Assert third row data (from page 1)
        $this->assertIsArray($result[2]);
        $this->assertEquals(200, $result[2]['user_id']);
        $this->assertEquals('bob', $result[2]['username']);
    }

    public function test_it_should_throw_exception_on_query_error()
    {
        // Create a query builder
        $queryBuilder = TimestreamBuilder::query()->from('test_database', 'test_table');

        // Mock the query client to throw TimestreamQueryException
        $this->mockQueryClient
            ->shouldReceive('query')
            ->once()
            ->andThrow(new TimestreamQueryException('Query failed', Mockery::mock(\Aws\CommandInterface::class)));

        // Expect FailTimestreamQueryException to be thrown
        $this->expectException(FailTimestreamQueryException::class);

        // Execute the query
        $readerDto = TimestreamReaderDto::make($queryBuilder);
        $this->service->query($readerDto);
    }

    /**
     * Helper method to invoke protected/private methods for testing
     *
     * @param mixed $object
     */
    private function invokeProtectedMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
