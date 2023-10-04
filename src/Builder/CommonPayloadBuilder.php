<?php

namespace NorbyBaru\AwsTimestream\Builder;

use Illuminate\Support\Carbon;
use NorbyBaru\AwsTimestream\Enum\ValueTypeEnum;

class CommonPayloadBuilder
{
    protected array $commonDimensions = [];
    protected array $commonAttributes = [];

    public static function make(): self
    {
        return new self();
    }

    public function setCommonDimensions(string $name, mixed $value): self
    {
        $this->commonDimensions[] = [
            'Name' => $name,
            'Value' => $value,
            'DimensionValueType' => ValueTypeEnum::VARCHAR->value,
        ];

        return $this;
    }

    public function setCommonMeasureValueType(ValueTypeEnum $type): self
    {
        $this->commonAttributes['MeasureValueType'] = $type->value;

        return $this;
    }

    public function setCommonTime(Carbon $time): self
    {
        $this->commonAttributes['Time'] = $this->getPreciseTime($time);

        return $this;
    }

    public function setCommonVersion(int $version): self
    {
        $this->commonAttributes['Version'] = $version;

        return $this;
    }

    public function toArray(): array
    {
        $common = [];
        if ($this->commonDimensions) {
            $common = [
                'Dimensions' => $this->commonDimensions,
            ];
        }

        return [
            ...$this->commonAttributes,
            ...$common,
        ];
    }

    private function getPreciseTime(Carbon $time): string
    {
        return (string) $time->getPreciseTimestamp(3);
    }
}
