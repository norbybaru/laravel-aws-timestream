<?php

namespace Ringierimu\AwsTimestream\Tests\Feature;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Ringierimu\AwsTimestream\Dto\TimestreamWriterDto;
use Ringierimu\AwsTimestream\Tests\TestCase;
use Ringierimu\AwsTimestream\TimestreamBuilder;
use Ringierimu\AwsTimestream\TimestreamService;

class WriterFeatureTest extends TestCase
{
    public function test_it_should_ingest_random_data()
    {
        $metrics = $this->generateMetrics();

        $payload = TimestreamBuilder::payload(
            $metrics['measure_name'],
            $metrics['measure_value'],
            $metrics['time'],
            'VARCHAR',
            $metrics['dimensions'],
        )->toArray();

        $timestreamWriter = TimestreamWriterDto::make($payload)->forTable('test');

        /** @var TimestreamService */
        $timestreamService = app(TimestreamService::class);
        $result = $timestreamService->write($timestreamWriter);

        $this->assertAwsResults($result, count($payload));
    }

    public function test_it_should_batch_ingest_data()
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

        /** @var TimestreamService */
        $timestreamService = app(TimestreamService::class);
        $result = $timestreamService->write($timestreamWriter);

        $this->assertAwsResults($result, count($payload));
    }

    private function assertAwsResults($result, int $totalRecords)
    {
        $this->assertInstanceOf(\Aws\Result::class, $result);
        $this->assertEquals(200, Arr::get($result->get('@metadata') ?? [], 'statusCode'));
        $this->assertEquals($totalRecords, Arr::get($result->get('RecordsIngested') ?? [], 'Total'));
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
