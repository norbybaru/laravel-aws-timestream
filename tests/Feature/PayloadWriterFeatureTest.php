<?php

namespace NorbyBaru\AwsTimestream\Tests\Feature;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use NorbyBaru\AwsTimestream\Builder\CommonPayloadBuilder;
use NorbyBaru\AwsTimestream\Builder\TimestreamPayloadBuilder;
use NorbyBaru\AwsTimestream\Dto\TimestreamWriterDto;
use NorbyBaru\AwsTimestream\Enum\ValueTypeEnum;
use NorbyBaru\AwsTimestream\Tests\TestCase;
use NorbyBaru\AwsTimestream\TimestreamService;

class PayloadWriterFeatureTest extends TestCase
{
    /**
     * Writing of Multi-measure attributes
     */
    public function it_should_ingest_multi_measure_records()
    {
        $filePath = __DIR__ . "/../Fixtures/data/sample.csv";
        $records = [];
        foreach ($this->readCSV($filePath) as $index => $row) {
            $data = explode(";", $row[0]);
            $payload = TimestreamPayloadBuilder::make(measureName: 'metric');

            $payload
                ->setDimensions(name: $data[0], value: $data[1])
                ->setDimensions(name: $data[2], value: $data[3])
                ->setDimensions(name: $data[4], value: $data[5])
                ->setMultiMeasuresValues(name: $data[6], value: $data[7], type: ValueTypeEnum::from($data[8]))
                ->setMultiMeasuresValues(name: $data[9], value: $data[10], type: ValueTypeEnum::from($data[11]))
                ->setMultiMeasuresValues(name: 'agent', value: $this->faker->userAgent, type: ValueTypeEnum::VARCHAR());

            $payload->setVersion(Carbon::now()->subMilliseconds($index * 50)->timestamp);

            $payload->setTime(Carbon::now()->subMilliseconds($index * 50));
            $records = [
                ...$records,
                ...$payload->toRecords(),
            ];

            if (count($records) === 100) {
                $timestreamWriter = TimestreamWriterDto::make($records)->forTable('test');

                /** @var TimestreamService */
                $timestreamService = app(TimestreamService::class);
                $result = $timestreamService->write($timestreamWriter);
                $this->assertAwsResults($result, count($records));
                $records = [];
            }
        }
    }

    /**
     * Writing of single record attributes
     */
    public function test_it_should_ingest_single_measure_record()
    {
        $payload = TimestreamPayloadBuilder::make(measureName: 'device')
            ->setMeasureValue(value: $this->faker->randomDigit)
            ->setDimensions(name: "mac_address", value: $this->faker->macAddress)
            ->setDimensions(name: "ref", value: $this->faker->uuid)
            ->setTime(Carbon::now());

        $timestreamWriter = TimestreamWriterDto::make($payload->toRecords())->forTable('test');

        /** @var TimestreamService */
        $timestreamService = app(TimestreamService::class);
        $result = $timestreamService->write($timestreamWriter);

        $this->assertAwsResults($result, count($payload->toRecords()));
    }

    /**
     * Writing batches of records with common attributes
     */
    public function test_it_should_batch_ingest_data()
    {
        $payloads = [
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
            ->setCommonMeasureValueType(ValueTypeEnum::VARCHAR())
            ->setCommonTime(Carbon::now())
            ->toArray();

        $timestreamWriter = TimestreamWriterDto::make($payloads, $common, 'test');

        /** @var TimestreamService */
        $timestreamService = app(TimestreamService::class);
        $result = $timestreamService->write($timestreamWriter);

        $this->assertAwsResults($result, count($payloads));
    }

    private function assertAwsResults($result, int $totalRecords)
    {
        $this->assertInstanceOf(\Aws\Result::class, $result);
        $this->assertEquals(200, Arr::get($result->get('@metadata') ?? [], 'statusCode'));
        $this->assertEquals($totalRecords, Arr::get($result->get('RecordsIngested') ?? [], 'Total'));
    }
}
