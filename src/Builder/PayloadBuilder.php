<?php

namespace NorbyBaru\AwsTimestream\Builder;

use Illuminate\Support\Carbon;
use NorbyBaru\AwsTimestream\Contract\PayloadBuilderContract;
use NorbyBaru\AwsTimestream\Enum\ValueTypeEnum;

final class PayloadBuilder implements PayloadBuilderContract
{
    protected array $commonDimensions = [];
    protected array $commonAttributes = [];
    protected array $dimensions = [];
    protected array $measureValues = [];
    protected ?int $version = null;

    public function __construct(
        protected string $measureName,
        protected mixed $measureValue = null,
        protected ?Carbon $time = null,
        protected ?ValueTypeEnum $measureValueType = null,
        array $dimensions = []
    ) {
        if ($dimensions) {
            collect($dimensions)->each(fn ($value, $key) => $this->buildDimensions($key, $value));
        }
    }

    public static function make(
        string $measureName,
        mixed $measureValue = null,
        ?Carbon $time = null,
        ?string $measureValueType = null,
        array $dimensions = []
    ): self {
        return new self(
            measureName: $measureName,
            measureValue: $measureValue,
            time: $time,
            measureValueType: $measureValueType ? ValueTypeEnum::from($measureValueType) : null,
            dimensions: $dimensions
        );
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

    public function setMultiMeasuresValues(string $name, mixed $value, ValueTypeEnum $type = ValueTypeEnum::VARCHAR): self
    {
        $this->measureValues[] = [
            'Name' => $name,
            'Value' => $value,
            'Type' => $type->value
        ];

        return $this;
    }

    private function buildDimensions(string $name, $value)
    {
        $this->dimensions[] = [
            'Name' => $name,
            'Value' => (string) $value,
        ];
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

    public static function buildCommonAttributes(array $attributes): array
    {
        $metrics = collect($attributes)
            ->map(function ($value, $key) {
                return [
                    'DimensionValueType' => 'VARCHAR',
                    'Name' => $key,
                    'Value' => $value,
                ];
            })
            ->values()
            ->all();

        return [
            'Dimensions' => $metrics,
        ];
    }

    private function getPreciseTime(Carbon $time): string
    {
        return (string) $time->getPreciseTimestamp(3);
    }

    public function getRecords(bool $batch = false): array
    {
        return $this->toArray($batch);
    }

    public function toArray(bool $batch = false): array
    {
        $metric = [
            'MeasureName' => $this->measureName,
            'MeasureValue' => (string) $this->measureValue,
        ];

        if ($this->measureValueType) {
            $metric['MeasureValueType'] = $this->measureValueType->value;
        }

        if ($this->time) {
            $metric['Time'] = $this->getPreciseTime($this->time);
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

        if (!$batch) {
            return [$metric];
        }

        return $metric;
    }
}
