<?php

namespace Ringierimu\AwsTimestream\Builder;

class QueryBuilder extends Builder
{
    public function __construct()
    {
        if (method_exists($this, 'handle')) {
            $this->handle();
        }
    }

    public static function query(): self
    {
        return new static();
    }
}
