<?php

namespace NorbyBaru\AwsTimestream\Contract;

use Illuminate\Support\Carbon;

interface PayloadBuilderContract
{
    public static function make(string $measureName): self;

    public static function buildCommonAttributes(array $attributes): array;

    public function toArray(bool $batch = false): array;
}
