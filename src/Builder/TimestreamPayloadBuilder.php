<?php

namespace NorbyBaru\AwsTimestream\Builder;

use Illuminate\Support\Carbon;
use NorbyBaru\AwsTimestream\Enum\ValueTypeEnum;

class TimestreamPayloadBuilder
{
    protected array $commonDimensions = [];
    protected array $commonAttributes = [];
    protected array $dimensions = [];
    protected array $measureValues = [];

    protected ?int $version = null;
    protected ?Carbon $time = null;

    public function __construct(
        protected string $measureName,
        protected mixed $measureValue = null,
        protected ?ValueTypeEnum $measureValueType = null
    ) {
    }

    public function setMeasureName(string $measureName): self
    {
        $this->measureName = $measureName;

        return $this;
    }

    public function setMeasureValue(mixed $value): self
    {
        $this->measureValue = $value;

        return $this;
    }

    public function setMeasureValueType(ValueTypeEnum $type): self
    {
        $this->measureValueType = $type;

        return $this;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function setMultiMeasuresValues(string $name, mixed $value, ?ValueTypeEnum $type = null): self
    {
        $this->measureValues[] = [
            'Name' => $name,
            'Value' => $value,
            'Type' => $type->value ?? ValueTypeEnum::VARCHAR()->value,
        ];

        return $this;
    }

    public function setDimensions(string $name, mixed $value): self
    {
        $this->dimensions[] = [
            'Name' => $name,
            'Value' => $value,
        ];

        return $this;
    }

    public function setTime(Carbon $carbon): self
    {
        $this->time = $carbon;

        return $this;
    }

    private function getPreciseTime(Carbon $time): string
    {
        return (string) $time->getPreciseTimestamp(3);
    }

    public function toRecords(): array
    {
        return [$this->toArray()];
    }

    public static function make(string $measureName): self
    {
        return new self($measureName);
    }

    public function toArray(): array
    {
        $metric = [
            'MeasureName' => $this->measureName,
            'MeasureValue' => (string) $this->measureValue,
        ];

        if ($this->time) {
            $metric['Time'] = $this->getPreciseTime($this->time);
        }

        if ($this->measureValueType) {
            $metric['MeasureValueType'] = $this->measureValueType->value;
        }

        if ($this->measureValues) {
            $metric['MeasureValues'] = $this->measureValues;
            $metric['MeasureValueType'] = 'MULTI';
            unset($metric['MeasureValue']);
        }

        if ($this->dimensions) {
            $metric['Dimensions'] = $this->dimensions;
        }

        if ($this->version) {
            $metric['Version'] = $this->version;
        }

        return $metric;
    }
}
