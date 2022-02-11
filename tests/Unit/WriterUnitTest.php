<?php

namespace Ringierimu\AwsTimestream\Tests\Unit;

use Illuminate\Support\Carbon;
use Ringierimu\AwsTimestream\Contract\PayloadBuilderContract;
use Ringierimu\AwsTimestream\Dto\TimestreamWriterDto;
use Ringierimu\AwsTimestream\Tests\TestCase;
use Ringierimu\AwsTimestream\TimestreamBuilder;

class WriterUnitTest extends TestCase
{
    public function test_it_should_return_instance_of_payload_builder()
    {
        $metrics = $this->generateMetrics();

        $payload = TimestreamBuilder::payload(
            $metrics['measure_name'],
            $metrics['measure_value'],
            $metrics['time'],
            'DOUBLE',
            $metrics['dimensions']
        );

        $this->assertInstanceOf(PayloadBuilderContract::class, $payload);
    }

    public function test_it_should_return_correct_payload_builder_structure()
    {
        $metrics = $this->generateMetrics();

        $payload = TimestreamBuilder::payload(
            $metrics['measure_name'],
            $metrics['measure_value'],
            $metrics['time'],
            'DOUBLE',
            $metrics['dimensions']
        )->toArray();

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('Dimensions', $payload);
        $this->assertArrayHasKey('MeasureName', $payload);
        $this->assertArrayHasKey('MeasureValue', $payload);
        $this->assertArrayHasKey('MeasureValueType', $payload);
        $this->assertArrayHasKey('Time', $payload);
    }

    public function test_it_should_return_accurate_payload_values()
    {
        $metrics = $this->generateMetrics();

        $payload = TimestreamBuilder::payload(
            $metrics['measure_name'],
            $metrics['measure_value'],
            $metrics['time'],
            'DOUBLE',
            $metrics['dimensions']
        )->toArray();

        $this->assertEquals($metrics['measure_name'], $payload['MeasureName']);
        $this->assertEquals($metrics['measure_value'], $payload['MeasureValue']);
        $this->assertEquals('DOUBLE', $payload['MeasureValueType']);
        $this->assertCount(count($metrics['dimensions']), $payload['Dimensions']);
        $this->assertEquals($metrics['time']->getPreciseTimestamp(3), $payload['Time']);
    }

    public function test_it_should_return_correct_writer_dto_structure()
    {
        $metrics = $this->generateMetrics();

        $payload = TimestreamBuilder::payload(
            $metrics['measure_name'],
            $metrics['measure_value'],
            $metrics['time'],
            'DOUBLE',
            $metrics['dimensions']
        )->toArray();

        $timestreamWriter = TimestreamWriterDto::make($payload)->forTable('test');
        $this->assertInstanceOf(TimestreamWriterDto::class, $timestreamWriter);

        $payload = $timestreamWriter->toArray();
        $this->assertArrayHasKey('DatabaseName', $payload);
        $this->assertArrayHasKey('Records', $payload);
        $this->assertArrayHasKey('TableName', $payload);
    }

    public function test_it_should_include_common_attributes_data()
    {
        $metrics = [
            [
                'measure_name' => 'cpu_usage',
                'measure_value' => $this->faker->randomDigit,
                'time' => Carbon::now(),
                'dimensions' => [
                    'ref' => $this->faker->uuid,
                ],
            ],
            [
                'measure_name' => 'memory_usage',
                'measure_value' => $this->faker->randomDigit,
                'time' => Carbon::now(),
                'dimensions' => [
                    'ref' => $this->faker->uuid,
                ],
            ],
        ];

        $commonAttributes['device_name'] = $this->faker->name;
        $commonAttributes['mac_address'] = $this->faker->macAddress;

        $common = TimestreamBuilder::commonAttributes($commonAttributes);

        collect($metrics)->map(function ($metric) {
            return TimestreamBuilder::payload(
                $metric['measure_name'],
                $metric['measure_value'],
                $metric['time'],
                'VARCHAR',
                $metric['dimensions'],
            )
            ->toArray();
        });

        $timestreamWriter = TimestreamWriterDto::make($metrics, $common, 'test');
        $payload = $timestreamWriter->toArray();

        $this->assertArrayHasKey('CommonAttributes', $payload);
        $this->assertCount(count($commonAttributes), $payload['CommonAttributes']['Dimensions']);

        $firstCommon = $payload['CommonAttributes']['Dimensions'][0];
        $this->assertArrayHasKey('DimensionValueType', $firstCommon);
        $this->assertArrayHasKey('Name', $firstCommon);
        $this->assertArrayHasKey('Value', $firstCommon);
    }

    public function test_it_should_ingest_to_correct_database_name_and_table_name()
    {
        $metrics = $this->generateMetrics();

        $payload = TimestreamBuilder::payload(
            $metrics['measure_name'],
            $metrics['measure_value'],
            $metrics['time'],
            'DOUBLE',
            $metrics['dimensions']
        )->toArray();

        $table = 'test';
        $timestreamWriter = TimestreamWriterDto::make($payload)->forTable($table);
        $payload = $timestreamWriter->toArray();

        $this->assertEquals(config('timestream.database'), $payload['DatabaseName']);
        $this->assertEquals(config("timestream.tables.sources.{$table}"), $payload['TableName']);

        $timestreamWriter = TimestreamWriterDto::make($payload, [], $table);
        $payload = $timestreamWriter->toArray();

        $this->assertEquals(config('timestream.database'), $payload['DatabaseName']);
        $this->assertEquals(config("timestream.tables.sources.{$table}"), $payload['TableName']);
    }

    private function generateMetrics(): array
    {
        return [
            'measure_name' => $this->faker->slug,
            'measure_value' => $this->faker->randomDigit,
            'time' => Carbon::now(),
            'dimensions' => [
                'mac_address' => $this->faker->macAddress,
                'ref' => $this->faker->uuid,
            ],
        ];
    }
}
