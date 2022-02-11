<?php

namespace Ringierimu\AwsTimestream;

use Illuminate\Support\Carbon;
use Ringierimu\AwsTimestream\Builder\PayloadBuilder;
use Ringierimu\AwsTimestream\Builder\QueryBuilder;
use Ringierimu\AwsTimestream\Contract\PayloadBuilderContract;
use Ringierimu\AwsTimestream\Contract\QueryBuilderContract;

class TimestreamBuilder
{
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

    public static function query(): QueryBuilderContract
    {
        return QueryBuilder::query();
    }
}
