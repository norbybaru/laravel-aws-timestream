<?php

namespace NorbyBaru\AwsTimestream\Tests\Unit;

use Illuminate\Support\Carbon;
use NorbyBaru\AwsTimestream\Builder\PayloadBuilder;
use NorbyBaru\AwsTimestream\Tests\TestCase;

class PayloadBuilderUnitTest extends TestCase
{
    public function test_it_has_initialized_correctly()
    {
        // create a time of "2024-01-11T15:49:17+00:00" (that equals to 1704988157000)
        $time = Carbon::create(2024, 1, 11, 15, 49, 17, 'UTC');
        $payloadBuilder = PayloadBuilder::make(
            'test',
            1,
            $time,
            'DOUBLE'
        );


        try {
            $metric = $payloadBuilder->toArray(true);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->assertIsArray($metric);
        $this->assertArrayHasKey('MeasureName', $metric);
        $this->assertArrayHasKey('MeasureValue', $metric);
        $this->assertArrayHasKey('MeasureValueType', $metric);
        $this->assertArrayHasKey('Time', $metric);
        $this->assertArrayHasKey('Dimensions', $metric);

        $this->assertEquals('test', $metric['MeasureName']);
        $this->assertEquals('1', $metric['MeasureValue']);
        $this->assertEquals('DOUBLE', $metric['MeasureValueType']);
        $this->assertEquals("1704988157000", $metric['Time']);
        $this->assertEmpty($metric['Dimensions']);
    }
}