<?php

namespace NorbyBaru\AwsTimestream;

use Illuminate\Support\Carbon;
use NorbyBaru\AwsTimestream\Builder\Builder;
use NorbyBaru\AwsTimestream\Builder\PayloadBuilder;
use NorbyBaru\AwsTimestream\Builder\TimestreamQueryBuilder;
use NorbyBaru\AwsTimestream\Contract\PayloadBuilderContract;

class TimestreamBuilder
{
    public static function batchPayload(array $metrics): array
    {
        return collect($metrics)
            ->map(
                fn ($metric) =>
                self::payload(
                    $metric['measure_name'],
                    $metric['measure_value'],
                    $metric['time'],
                    $metric['measure_value_type'] ?? 'VARCHAR',
                    $metric['dimensions']
                )->toArray(true)
            )->all();
    }

    public static function payload(
        string $measureName,
        $measureValue,
        Carbon $time,
        string $measureValueType = 'DOUBLE',
        array $dimensions = []
    ): PayloadBuilderContract {
        return PayloadBuilder::make($measureName, $measureValue, $time, $measureValueType, $dimensions);
    }

    public static function commonAttributes(array $attributes): array
    {
        return PayloadBuilder::buildCommonAttributes($attributes);
    }

    public static function query(): Builder
    {
        return TimestreamQueryBuilder::query();
    }
}
