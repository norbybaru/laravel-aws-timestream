<?php

namespace NorbyBaru\AwsTimestream\Builder;

final class TimestreamQueryBuilder extends Builder
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
    protected function builder(): void
    {}

    public static function query(): self
    {
        return new self();
    }
}
