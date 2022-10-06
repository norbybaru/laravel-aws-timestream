<?php

namespace NorbyBaru\AwsTimestream\Builder;

class TimestreamQueryBuilder extends Builder
{
    public function __construct()
    {
        if (method_exists($this, 'builder')) {
            $this->builder();
        }
    }

    public static function query(): self
    {
        return new static();
    }
}
