<?php

namespace Ringierimu\AwsTimestream\Query;

use Illuminate\Support\Str;
use Ringierimu\AwsTimestream\Contract\TimestreamQueryContract;
use Ringierimu\AwsTimestream\Trait\CanUseTimestreamQuery;

abstract class TimestreamQuery implements TimestreamQueryContract
{
    use CanUseTimestreamQuery;

    private string $queryString = '';
    private string $fromQuery = '';
    private string $whereQuery = '';
    private string $selectStatement = '';
    private string $orderByQuery = '';
    private string $groupByQuery = '';
    private array $withQueries = [];

    public function getSql(): string
    {
        return $this->getQueryString();
    }

    public function toSql(): string
    {
        return $this->getSql();
    }

    public function getFromQuery(): string
    {
        return $this->fromQuery;
    }

    public function getSelectStatement(): string
    {
        return $this->selectStatement;
    }

    public function getWhereQuery(): string
    {
        return $this->whereQuery;
    }

    public function getOrderByQuey(): string
    {
        return $this->orderByQuery;
    }

    public function getWithQueries(): array
    {
        return $this->withQueries;
    }

    public function getQueryString(): string
    {
        if ($this->withQueries) {
            $withQueries = 'WITH ' . implode(',', $this->withQueries);
            $queryString = Str::of($withQueries)
                ->append(' ')
                ->append($this->selectStatement);
        } else {
            $queryString = Str::of($this->selectStatement);
        }

        if ($this->fromQuery) {
            $queryString = $queryString
                ->append(' ')
                ->append($this->fromQuery);
        }

        if ($this->whereQuery) {
            $queryString = $queryString
                ->append(' ')
                ->append($this->whereQuery);
        }

        if ($this->groupByQuery) {
            $queryString = $queryString
                ->append(' ')
                ->append($this->groupByQuery);
        }

        if ($this->orderByQuery) {
            $queryString = $queryString
                ->append(' ')
                ->append($this->orderByQuery);
        }

        return $queryString;
    }
}
