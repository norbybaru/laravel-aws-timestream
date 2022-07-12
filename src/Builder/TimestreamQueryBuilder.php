<?php

namespace NorbyBaru\AwsTimestream\Builder;

class TimestreamQueryBuilder extends Builder
{
    final public function __construct()
    {
        $this->builder();
    }

    /**
     * Build SQL query
     */
    public function builder(): void
    {
    }

    public static function query(): self
    {
        return new static();
    }
}
