<?php

namespace NorbyBaru\AwsTimestream\Tests\Unit;

use Illuminate\Support\Carbon;
use NorbyBaru\AwsTimestream\Builder\CommonPayloadBuilder;
use NorbyBaru\AwsTimestream\Builder\TimestreamPayloadBuilder;
use NorbyBaru\AwsTimestream\Contract\PayloadBuilderContract;
use NorbyBaru\AwsTimestream\Dto\TimestreamWriterDto;
use NorbyBaru\AwsTimestream\Enum\ValueTypeEnum;
use NorbyBaru\AwsTimestream\Tests\TestCase;
use NorbyBaru\AwsTimestream\TimestreamBuilder;

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

        $payload = TimestreamPayloadBuilder::make(measureName: 'device')
            ->setMeasureValue(value: $this->faker->randomDigit)
            ->setMeasureValueType(type: ValueTypeEnum::DOUBLE())
            ->setTime(Carbon::now())
            ->setDimensions(name: 'mac_address', value: $this->faker->macAddress)
            ->setDimensions(name: 'ref', value: $this->faker->uuid);

        $this->assertInstanceOf(TimestreamPayloadBuilder::class, $payload);
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
        $this->assertCount(1, $payload);
        $this->assertArrayHasKey('Dimensions', $payload[0]);
        $this->assertArrayHasKey('MeasureName', $payload[0]);
        $this->assertArrayHasKey('MeasureValue', $payload[0]);
        $this->assertArrayHasKey('MeasureValueType', $payload[0]);
        $this->assertArrayHasKey('Time', $payload[0]);

        $payload = TimestreamPayloadBuilder::make(measureName: 'device')
            ->setMeasureValue(value: $this->faker->randomDigit)
            ->setMeasureValueType(type: ValueTypeEnum::DOUBLE())
            ->setTime(Carbon::now())
            ->setDimensions(name: 'mac_address', value: $this->faker->macAddress)
            ->setDimensions(name: 'ref', value: $this->faker->uuid)
            ->toRecords();

        $this->assertIsArray($payload);
        $this->assertCount(1, $payload);
        $this->assertArrayHasKey('Dimensions', $payload[0]);
        $this->assertArrayHasKey('MeasureName', $payload[0]);
        $this->assertArrayHasKey('MeasureValue', $payload[0]);
        $this->assertArrayHasKey('MeasureValueType', $payload[0]);
        $this->assertArrayHasKey('Time', $payload[0]);
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

        $this->assertEquals($metrics['measure_name'], $payload[0]['MeasureName']);
        $this->assertEquals($metrics['measure_value'], $payload[0]['MeasureValue']);
        $this->assertEquals('DOUBLE', $payload[0]['MeasureValueType']);
        $this->assertCount(count($metrics['dimensions']), $payload[0]['Dimensions']);
        $this->assertEquals($metrics['time']->getPreciseTimestamp(3), $payload[0]['Time']);

        $measureValue = $this->faker->randomDigit;
        $now = Carbon::now();
        $payload = TimestreamPayloadBuilder::make(measureName: 'device')
            ->setMeasureValue(value: $measureValue)
            ->setMeasureValueType(type: ValueTypeEnum::DOUBLE())
            ->setTime($now)
            ->setDimensions(name: 'mac_address', value: $this->faker->macAddress)
            ->setDimensions(name: 'ref', value: $this->faker->uuid)
            ->toRecords();

        $this->assertEquals('device', $payload[0]['MeasureName']);
        $this->assertEquals($measureValue, $payload[0]['MeasureValue']);
        $this->assertEquals(ValueTypeEnum::DOUBLE()->value, $payload[0]['MeasureValueType']);
        $this->assertCount(2, $payload[0]['Dimensions']);
        $this->assertEquals($now->getPreciseTimestamp(3), $payload[0]['Time']);
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

        $payload = TimestreamPayloadBuilder::make(measureName: 'device')
            ->setMeasureValue(value: $this->faker->randomDigit)
            ->setMeasureValueType(type: ValueTypeEnum::DOUBLE())
            ->setTime(Carbon::now())
            ->setDimensions(name: 'mac_address', value: $this->faker->macAddress)
            ->setDimensions(name: 'ref', value: $this->faker->uuid)
            ->toRecords();

        $timestreamWriter = TimestreamWriterDto::make($payload)->forTable('test');
        $this->assertInstanceOf(TimestreamWriterDto::class, $timestreamWriter);

        $payload = $timestreamWriter->toArray();
        $this->assertArrayHasKey('DatabaseName', $payload);
        $this->assertArrayHasKey('Records', $payload);
        $this->assertArrayHasKey('TableName', $payload);
    }

    public function test_it_should_return_correct_data_for_batch_ingestion()
    {
        $metrics = [
            [
                'measure_name' => 'cpu_usage',
                'measure_value' => $this->faker->randomDigit,
                'measure_value_type' => 'VARCHAR',
                'time' => Carbon::now(),
                'dimensions' => [
                    'ref' => $this->faker->uuid,
                ],
            ],
            [
                'measure_name' => 'memory_usage',
                'measure_value' => $this->faker->randomDigit,
                'measure_value_type' => 'DOUBLE',
                'time' => Carbon::now(),
                'dimensions' => [
                    'ref' => $this->faker->uuid,
                ],
            ],
        ];

        $payload = TimestreamBuilder::batchPayload($metrics);

        $this->assertIsArray($payload);
        $this->assertCount(2, $payload);

        foreach ($payload as $index => $data) {
            $this->assertEquals($metrics[$index]['measure_name'], $data['MeasureName']);
            $this->assertEquals($metrics[$index]['measure_value'], $data['MeasureValue']);
            $this->assertEquals($metrics[$index]['measure_value_type'], $data['MeasureValueType']);
            $this->assertCount(count($metrics[$index]['dimensions']), $data['Dimensions']);
            $this->assertEquals($metrics[$index]['time']->getPreciseTimestamp(3), $data['Time']);
        }

        $payloads = [
            ...TimestreamPayloadBuilder::make(measureName: 'cpu_usage')
                ->setMeasureValue(value: $this->faker->randomFloat(5, 1, 100))
                ->setMeasureValueType(type: ValueTypeEnum::DOUBLE())
                ->setDimensions(name: "ref", value: $this->faker->uuid)
                ->setTime(Carbon::now())
                ->toRecords(),
            ...TimestreamPayloadBuilder::make(measureName: 'memory_usage')
                ->setMeasureValue(value: $this->faker->randomFloat(5, 1, 100))
                ->setMeasureValueType(type: ValueTypeEnum::DOUBLE())
                ->setDimensions(name: "ref", value: $this->faker->uuid)
                ->setTime(Carbon::now())
                ->toRecords(),
        ];

        $this->assertIsArray($payloads);
        $this->assertCount(2, $payloads);

        foreach ($payloads as $index => $data) {
            $this->assertEquals($payloads[$index]['MeasureName'], $data['MeasureName']);
            $this->assertEquals($payloads[$index]['MeasureValue'], $data['MeasureValue']);
            $this->assertEquals($payloads[$index]['MeasureValueType'], $data['MeasureValueType']);
            $this->assertCount(count($payloads[$index]['Dimensions']), $data['Dimensions']);
            $this->assertEquals($payloads[$index]['Time'], $data['Time']);
        }
    }

    public function test_it_should_return_correct_dto_structure_for_batch_ingestion_data()
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

        $payload = TimestreamBuilder::batchPayload($metrics);

        $timestreamWriter = TimestreamWriterDto::make($payload)->forTable('test');
        $this->assertInstanceOf(TimestreamWriterDto::class, $timestreamWriter);

        $payload = $timestreamWriter->toArray();
        $this->assertArrayHasKey('DatabaseName', $payload);
        $this->assertArrayHasKey('Records', $payload);
        $this->assertArrayHasKey('TableName', $payload);

        $payload = [
            ...TimestreamPayloadBuilder::make(measureName: 'cpu_usage')
                ->setMeasureValue(value: $this->faker->randomFloat(5, 1, 100))
                ->setMeasureValueType(type: ValueTypeEnum::DOUBLE())
                ->setDimensions(name: "ref", value: $this->faker->uuid)
                ->setTime(Carbon::now())
                ->toRecords(),
            ...TimestreamPayloadBuilder::make(measureName: 'memory_usage')
                ->setMeasureValue(value: $this->faker->randomFloat(5, 1, 100))
                ->setMeasureValueType(type: ValueTypeEnum::DOUBLE())
                ->setDimensions(name: "ref", value: $this->faker->uuid)
                ->setTime(Carbon::now())
                ->toRecords(),
        ];

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

        $payload = TimestreamBuilder::batchPayload($metrics);

        $timestreamWriter = TimestreamWriterDto::make($payload, $common, 'test');
        $payload = $timestreamWriter->toArray();

        $this->assertArrayHasKey('CommonAttributes', $payload);
        $this->assertCount(count($commonAttributes), $payload['CommonAttributes']['Dimensions']);

        $firstCommon = $payload['CommonAttributes']['Dimensions'][0];
        $this->assertArrayHasKey('DimensionValueType', $firstCommon);
        $this->assertArrayHasKey('Name', $firstCommon);
        $this->assertArrayHasKey('Value', $firstCommon);

        $payload = [
            ...TimestreamPayloadBuilder::make(measureName: 'cpu_usage')
                ->setMeasureValue(value: $this->faker->randomFloat(5, 1, 100))
                ->setDimensions(name: "ref", value: $this->faker->uuid)
                ->toRecords(),
            ...TimestreamPayloadBuilder::make(measureName: 'memory_usage')
                ->setMeasureValue(value: $this->faker->randomFloat(5, 1, 100))
                ->setDimensions(name: "ref", value: $this->faker->uuid)
                ->toRecords(),
        ];

        $common = CommonPayloadBuilder::make()
            ->setCommonDimensions(name: 'processor', value: $this->faker->linuxProcessor)
            ->setCommonDimensions(name: 'mac_address', value: $this->faker->macAddress)
            ->setCommonMeasureValueType(ValueTypeEnum::DOUBLE())
            ->setCommonTime(Carbon::now())
            ->toArray();

        $timestreamWriter = TimestreamWriterDto::make($payload, $common, 'test');
        $payload = $timestreamWriter->toArray();

        $this->assertArrayHasKey('CommonAttributes', $payload);
        $commonAttributes = $payload['CommonAttributes'];

        $this->assertArrayHasKey('MeasureValueType', $commonAttributes);
        $this->assertArrayHasKey('Time', $commonAttributes);
        $this->assertArrayHasKey('Dimensions', $commonAttributes);
        $this->assertCount(count($common), $commonAttributes);
        $this->assertCount(2, $commonAttributes['Dimensions']);

        $firstCommon = $commonAttributes['Dimensions'][0];
        $this->assertArrayHasKey('DimensionValueType', $firstCommon);
        $this->assertArrayHasKey('Name', $firstCommon);
        $this->assertArrayHasKey('Value', $firstCommon);
    }

    public function test_it_should_ingest_to_correct_database_name_and_table_name()
    {
        $payload = TimestreamPayloadBuilder::make(measureName: 'device')
            ->setMeasureValue(value: $this->faker->randomDigit)
            ->setMeasureValueType(type: ValueTypeEnum::DOUBLE())
            ->setTime(Carbon::now())
            ->setDimensions(name: 'mac_address', value: $this->faker->macAddress)
            ->setDimensions(name: 'ref', value: $this->faker->uuid)
            ->toRecords();

        $alias = 'test';
        $timestreamWriter = TimestreamWriterDto::make($payload)->forTable($alias);
        $payload = $timestreamWriter->toArray();

        $this->assertEquals(config('timestream.database'), $payload['DatabaseName']);
        $this->assertEquals(config("timestream.tables.aliases.{$alias}"), $payload['TableName']);

        $timestreamWriter = TimestreamWriterDto::make($payload, [], $alias);
        $payload = $timestreamWriter->toArray();

        $this->assertEquals(config('timestream.database'), $payload['DatabaseName']);
        $this->assertEquals(config("timestream.tables.aliases.{$alias}"), $payload['TableName']);
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
