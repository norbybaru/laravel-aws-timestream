<?php

namespace Ringierimu\AwsTimestream\Contract;

use Illuminate\Support\Carbon;

interface PayloadBuilderContract
{
    public static function make(string $measureName, $measureValue, Carbon $time, string $measureValueType = 'DOUBLE', array $dimensions = []): self;

    public static function buildCommonAttributes(array $attributes): array;

    public function toArray(): array;
}
