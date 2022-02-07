<?php

namespace Ringierimu\AwsTimestream\Query;

use Illuminate\Support\Str;

class TimestreamQueryBuilder extends TimestreamQuery
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

    public function mergeQuery(self $queryBuilder)
    {
        $builder = $queryBuilder::query();

        if ($withQueries = $builder->getWithQueries()) {
            $this->withQueries = $withQueries;
        }

        if ($select = $builder->getSelectStatement()) {
            $this->selectStatement = $select;
        }

        if ($builder->getWhereQuery()) {
            $query = $builder->getWhereQuery();
            if ($this->getWhereQuery()) {
                $query = sprintf(' AND %s', trim(str_trim_from($builder->getWhereQuery(), 'WHERE')));
            }

            $this->whereQuery = Str::of($this->whereQuery)->append($query);
        }

        if ($fromQuery = $builder->getFromQuery()) {
            $this->fromQuery = $fromQuery;
        }

        if ($orderBy = $builder->getOrderByQuey()) {
            $this->orderByQuery = $orderBy;
        }
    }
}
