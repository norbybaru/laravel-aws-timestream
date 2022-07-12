<?php

namespace NorbyBaru\AwsTimestream\Builder;

class TimestreamQueryBuilder extends Builder
{
    public function __construct()
    {
        $this->builder();
    }

    /**
     * Build SQL query
     *
     * @return void
     */
    public function builder(): void
    {}

    public static function query(): self
    {
        return new static();
    }
}
