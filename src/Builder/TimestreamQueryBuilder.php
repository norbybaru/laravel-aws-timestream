<?php

namespace NorbyBaru\AwsTimestream\Builder;

use NorbyBaru\AwsTimestream\Contract\CanBuildQueryContract;

class TimestreamQueryBuilder extends Builder implements CanBuildQueryContract
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function query(): self
    {
        return new static();
    }

    public static function newQuery(): self
    {
        return new self();
    }

    public static function getQuery(): string
    {
        return (new static)->toSql();
    }
}
