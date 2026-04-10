<?php

namespace NorbyBaru\AwsTimestream\Builder;

use Illuminate\Support\Carbon;
use NorbyBaru\AwsTimestream\Contract\PayloadBuilderContract;

final class PayloadBuilder implements PayloadBuilderContract
{
    protected array $dimensions = [];

    public function __construct(
        protected string $measureName,
        protected $measureValue,
        protected Carbon $time,
        protected string $measureValueType = 'DOUBLE',
        array $dimensions = []
    ) {
        if ($dimensions) {
            foreach ($dimensions as $key => $value) {
                $this->buildDimensions($key, $value);
            }
        }
    }

    public static function make(
        string $measureName,
        $measureValue,
        Carbon $time,
        string $measureValueType = 'DOUBLE',
        array $dimensions = []
    ): self {
        return new self($measureName, $measureValue, $time, $measureValueType, $dimensions);
    }

    private function buildDimensions(string $name, $value)
    {
        $this->dimensions[] = [
            'Name' => $name,
            'Value' => (string) $value,
        ];
    }

    public static function buildCommonAttributes(array $attributes): array
    {
        $metrics = array_values(array_map(function ($key, $value) {
            return [
                'DimensionValueType' => 'VARCHAR',
                'Name' => $key,
                'Value' => (string) $value,
            ];
        }, array_keys($attributes), $attributes));

        return [
            'Dimensions' => $metrics,
        ];
    }

    public function toArray(bool $batch = false): array
    {
        $metric = [
            'Dimensions' => $this->dimensions,
            'MeasureName' => $this->measureName,
            'MeasureValue' => (string) $this->measureValue,
            'MeasureValueType' => $this->measureValueType,
            'Time' => (string) $this->time->getPreciseTimestamp(3),
        ];

        if (!$batch) {
            return [$metric];
        }

        return $metric;
    }
}
