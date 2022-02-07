<?php

namespace Ringierimu\AwsTimestream\Support;

use Illuminate\Support\Carbon;

class TimestreamPayloadBuilder
{
    public array $dimensions = [];

    public array $measures = [];

    public string $measureName;

    public $measureValue;

    public string $measureValueType;

    public Carbon $time;

    public function __construct(
        string $measureName,
        $measureValue,
        Carbon $time,
        string $measureValueType = 'DOUBLE',
        array $dimensions = []
    ) {
        $this->buildMeasure($measureName, $measureValue, $time, $measureValueType);

        if ($dimensions) {
            collect($dimensions)->each(fn ($value, $key) => $this->buildDimensions($key, $value));
        }
    }

    public static function make(
        string $measureName,
        $measureValue,
        Carbon $time,
        string $measureValueType = 'DOUBLE',
        array $dimensions = []
    ): self {
        return new static($measureName, $measureValue, $time, $measureValueType, $dimensions);
    }

    private function buildMeasure(
        string $name,
        $value,
        Carbon $timestamp,
        string $measureValueType = 'DOUBLE'
    ): self {
        $this->measureName = $name;
        $this->measureValue = $value;
        $this->measureValueType = ucwords($measureValueType);
        $this->time = $timestamp;

        $this->measures = [
            'MeasureName' => $this->measureName,
            'MeasureValue' => $this->measureValue,
            'MeasureValueType' => ucwords($this->measureValueType),
            'Time' => $this->time->getTimestamp(),
        ];

        return $this;
    }

    private function buildDimensions(string $name, $value): self
    {
        $this->dimensions[] = [
            'Name' => $name,
            'Value' => (string) $value,
        ];

        return $this;
    }

    public function toArray(): array
    {
        return [
            'Dimensions' => $this->dimensions,
            'MeasureName' => $this->measureName,
            'MeasureValue' => (string) $this->measureValue,
            'MeasureValueType' => $this->measureValueType,
            'Time' => (string) $this->time->getPreciseTimestamp(3),
        ];
    }
}
