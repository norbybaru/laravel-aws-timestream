<?php

namespace Ringierimu\AwsTimestream\Builder;

final class QueryBuilder extends Builder
{
    public function __construct()
    {
        $this->execute();
    }

    public function execute()
    {
    }

    public static function query(): self
    {
        return new self();
    }
}
