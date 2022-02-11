<?php

namespace Ringierimu\AwsTimestream\Concerns;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ringierimu\AwsTimestream\Contract\QueryBuilderContract;

trait BuildersConcern
{
    public function selectRaw(string $statetement): QueryBuilderContract
    {
        $this->selectStatement = $statetement;

        return $this;
    }

    public function select(string $columns): QueryBuilderContract
    {
        $this->selectStatement = sprintf('SELECT %s', $columns);

        return $this;
    }

    public function from(string $database, string $table, string $alias = null): QueryBuilderContract
    {
        $this->database = $database;
        $this->table = $table;

        $this->fromQuery = 'FROM "' . $database . '"."' . $table . '"';

        if ($alias) {
            $this->fromQuery = Str::of($this->fromQuery)->append(" {$alias}");
        }

        return $this;
    }

    public function fromRaw(string $statetement): QueryBuilderContract
    {
        $this->fromQuery = $statetement;

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): QueryBuilderContract
    {
        $this->orderByQuery = sprintf('ORDER BY %s %s', $column, $direction);

        return $this;
    }

    public function groupBy($args): QueryBuilderContract
    {
        $columns = func_get_args();
        $this->groupByQuery = sprintf('GROUP BY %s', implode(', ', $columns));

        return $this;
    }

    /**
     * @param  mixed  $value
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $boolean
     * @param  bool  $ago
     */
    public function where(string $column, $value, string $operator = '=', string $boolean = 'and', bool $ago = false): QueryBuilderContract
    {
        $query = Str::of($this->whereQuery);

        $value = $value instanceof Closure
            // If the value is a Closure, it means the developer is performing an entire
            ? '(' . call_user_func($value) . ')'
            : $value;

        if ($query->length() == 0) {
            $whereQuery = $query->append(
                sprintf('WHERE %s %s %s', $column, $operator, $value)
            );

            if ($ago) {
                $whereQuery = $query->append(
                    sprintf('WHERE %s %s ago(%s)', $column, $operator, $value)
                );
            }

            $this->whereQuery = $whereQuery;

            return $this;
        }

        $whereQuery = $query->append(
            sprintf(' %s %s %s %s', mb_strtoupper($boolean), $column, $operator, $value)
        );

        if ($ago) {
            $whereQuery = $query->append(
                sprintf(' %s %s %s ago(%s)', mb_strtoupper($boolean), $column, $operator, $value)
            );
        }

        $this->whereQuery = $whereQuery;

        return $this;
    }

    /**
     * @param  mixed  $value
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $boolean
     */
    public function whereAgo(string $column, $value, string $operator = '=', string $boolean = 'and'): QueryBuilderContract
    {
        return $this->where($column, $value, $operator, $boolean, true);
    }

    /**
     * @param  mixed  $value
     * @param  string  $column
     * @param  string  $operator
     */
    public function andWhere(string $column, $value, string $operator = '='): QueryBuilderContract
    {
        return $this->where($column, $value, $operator);
    }

    public function whereIn(string $column, array|\Closure $values, string $boolean = 'and', $not = false): QueryBuilderContract
    {
        if (empty($values)) {
            return $this;
        }

        $query = Str::of($this->whereQuery);

        if ($query->length() == 0) {
            $query = $query->append('WHERE ');
        } else {
            $query = $query->append(
                sprintf(' %s ', mb_strtoupper($boolean))
            );
        }

        $operator = $not ? 'NOT IN' : 'IN';
        $query = $query
            ->append(
                sprintf('%s %s (', $column, $operator)
            );

        if ($values instanceof Closure) {
            $query = Str::of($query)
                ->append(
                    sprintf('%s', trim(call_user_func($values)))
                );
        } else {
            $counter = count($values);

            collect($values)->each(function ($value) use (&$counter, &$query) {
                $query = Str::of($query)
                    ->append(
                        sprintf('%s', trim("'$value'"))
                    );
                $counter--;
                if ($counter !== 0) {
                    $query = Str::of($query)->append(',');
                }
            });
        }

        $this->whereQuery = Str::of($query)->append(')');

        return $this;
    }

    public function whereNotIn(string $column, array|\Closure $values, string $boolean = 'and'): QueryBuilderContract
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function whereBetween(string $column, array $values, $boolean = 'and', $not = false): QueryBuilderContract
    {
        if (empty($values)) {
            return $this;
        }

        $query = Str::of($this->whereQuery);

        if ($query->length() == 0) {
            $query = $query->append('WHERE ');
        } else {
            $query = $query->append(
                sprintf(' %s ', mb_strtoupper($boolean))
            );
        }

        $type = 'BETWEEN';
        $operator = $not ? 'NOT ' : '';
        $operator .= $type;
        $query = $query
            ->append(
                sprintf('%s %s ', $column, $operator)
            );

        [$firstKey, $secondKey] = array_slice(Arr::flatten($values), 0, 2);

        $this->whereQuery = Str::of($query)
            ->append(sprintf("%s and %s", $firstKey, $secondKey));

        return $this;
    }

    public function whereNotBetween(string $column, array $values, $boolean = 'and'): QueryBuilderContract
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    public function whereNull(string|array $columns, $boolean = 'and', $not = false): QueryBuilderContract
    {
        $type = 'NULL';

        $operator = $not ? 'IS NOT ' : 'IS ';
        $operator .= $type;

        if (empty($columns)) {
            return $this;
        }

        $query = Str::of($this->whereQuery);

        if ($query->length() == 0) {
            $query = $query->append('WHERE ');
        } else {
            $query = $query->append(
                sprintf(' %s ', mb_strtoupper($boolean))
            );
        }

        $counter = 0;
        foreach (Arr::wrap($columns) as $column) {
            $counter++;
            $query = $query->append(sprintf('%s %s', $column, $operator));
            if ($counter < count(Arr::wrap($columns))) {
                $query = $query->append(sprintf(' %s ', mb_strtoupper($boolean)));
            }
        }

        $this->whereQuery = Str::of($query);

        return $this;
    }

    public function whereNotNull(string|array $columns, $boolean = 'and'): QueryBuilderContract
    {
        return $this->whereNull($columns, $boolean, true);
    }

    public function limitBy(int $limit): QueryBuilderContract
    {
        $this->limitByQuery = sprintf('LIMIT %s ', $limit);

        return $this;
    }

    public function withQuery(string $as, Closure $callback): QueryBuilderContract
    {
        $this->withQueries = array_merge($this->withQueries, [$as . ' AS (' . call_user_func($callback) . ')']);

        return $this;
    }

    public function mergeQuery(QueryBuilderContract $queryBuilder)
    {
        if ($withQueries = $queryBuilder->getWithQueries()) {
            $this->withQueries = $withQueries;
        }

        if ($select = $queryBuilder->getSelectStatement()) {
            $this->selectStatement = $select;
        }

        if ($queryBuilder->getWhereQuery()) {
            $query = $queryBuilder->getWhereQuery();
            if ($this->getWhereQuery()) {
                $query = sprintf(' AND %s', trim($this->strTrimFrom($queryBuilder->getWhereQuery(), 'WHERE')));
            }

            $this->whereQuery = Str::of($this->whereQuery)->append($query);
        }

        if ($fromQuery = $queryBuilder->getFromQuery()) {
            $this->fromQuery = $fromQuery;
        }

        if ($orderBy = $queryBuilder->getOrderByQuery()) {
            $this->orderByQuery = $orderBy;
        }
    }
}
