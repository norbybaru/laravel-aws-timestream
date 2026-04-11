<?php

namespace NorbyBaru\AwsTimestream\Builder;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use NorbyBaru\AwsTimestream\Concerns\BuildersConcern;
use NorbyBaru\AwsTimestream\Contract\QueryBuilderContract;

abstract class Builder implements QueryBuilderContract
{
    use BuildersConcern;
    use Macroable;

    protected string $database = '';
    protected string $table = '';
    protected string $fromQuery = '';
    protected string $whereQuery = '';
    protected string $selectStatement = '';
    protected string $orderByQuery = '';
    protected string $groupByQuery = '';
    protected string $limitByQuery = '';
    protected array $withQueries = [];

    public function selectRaw(string $statement): self
    {
        $this->selectStatement = $statement;

        return $this;
    }

    public function select(string $columns): self
    {
        $this->selectStatement = sprintf('SELECT %s', $columns);

        return $this;
    }

    public function from(string $database, string $table, string $alias = null): self
    {
        $this->database = $database;
        $this->table = $table;

        $this->fromQuery = 'FROM "' . $database . '"."' . $table . '"';

        if ($alias) {
            $this->fromQuery .= " {$alias}";
        }

        return $this;
    }

    public function fromRaw(string $statement): self
    {
        $this->fromQuery = $statement;

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderByQuery = sprintf('ORDER BY %s %s', $column, $direction);

        return $this;
    }

    public function groupBy($args): self
    {
        $columns = func_get_args();
        $this->groupByQuery = sprintf('GROUP BY %s', implode(', ', $columns));

        return $this;
    }

    public function where(string $column, $value, string $operator = '=', string $boolean = 'and', bool $ago = false): self
    {
        $query = $this->whereQuery;

        $value = $value instanceof Closure
            // If the value is a Closure, it means the developer is performing an entire
            ? '(' . call_user_func($value) . ')'
            : $value;

        if (strlen($query) == 0) {
            if ($ago) {
                $whereQuery = sprintf('WHERE %s %s ago(%s)', $column, $operator, $value);
            } else {
                $whereQuery = sprintf('WHERE %s %s %s', $column, $operator, $value);
            }

            $this->whereQuery = $whereQuery;

            return $this;
        }

        if ($ago) {
            $whereQuery = $query . sprintf(' %s %s %s ago(%s)', mb_strtoupper($boolean), $column, $operator, $value);
        } else {
            $whereQuery = $query . sprintf(' %s %s %s %s', mb_strtoupper($boolean), $column, $operator, $value);
        }

        $this->whereQuery = $whereQuery;

        return $this;
    }

    public function whereAgo(string $column, $value, string $operator = '=', string $boolean = 'and'): self
    {
        return $this->where($column, $value, $operator, $boolean, true);
    }

    public function andWhere(string $column, $value, string $operator = '='): self
    {
        return $this->where($column, $value, $operator);
    }

    public function whereIn(string $column, array|Closure $values, string $boolean = 'and', $not = false): self
    {
        if (empty($values)) {
            return $this;
        }

        $query = $this->whereQuery;

        if (strlen($query) == 0) {
            $query .= 'WHERE ';
        } else {
            $query .= sprintf(' %s ', mb_strtoupper($boolean));
        }

        $operator = $not ? 'NOT IN' : 'IN';
        $query .= sprintf('%s %s (', $column, $operator);

        if ($values instanceof Closure) {
            $query .= sprintf('%s', trim(call_user_func($values)));
        } else {
            $counter = count($values);

            collect($values)->each(function ($value) use (&$counter, &$query) {
                $query .= sprintf('%s', trim("'$value'"));
                $counter--;
                if ($counter !== 0) {
                    $query .= ',';
                }
            });
        }

        $query .= ')';
        $this->whereQuery = $query;

        return $this;
    }

    public function whereNotIn(string $column, array|Closure $values, string $boolean = 'and'): self
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function whereBetween(string $column, array $values, $boolean = 'and', $not = false): self
    {
        if (empty($values)) {
            return $this;
        }

        $query = $this->whereQuery;

        if (strlen($query) == 0) {
            $query .= 'WHERE ';
        } else {
            $query .= sprintf(' %s ', mb_strtoupper($boolean));
        }

        $type = 'BETWEEN';
        $operator = $not ? 'NOT ' : '';
        $operator .= $type;
        $query .= sprintf('%s %s ', $column, $operator);

        [$firstKey, $secondKey] = array_slice(Arr::flatten($values), 0, 2);

        $query .= sprintf("%s and %s", $firstKey, $secondKey);
        $this->whereQuery = $query;

        return $this;
    }

    public function whereNotBetween(string $column, array $values, $boolean = 'and'): self
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    public function whereNull(string|array $columns, $boolean = 'and', $not = false): self
    {
        $type = 'NULL';

        $operator = $not ? 'IS NOT ' : 'IS ';
        $operator .= $type;

        if (empty($columns)) {
            return $this;
        }

        $query = $this->whereQuery;

        if (strlen($query) == 0) {
            $query .= 'WHERE ';
        } else {
            $query .= sprintf(' %s ', mb_strtoupper($boolean));
        }

        $counter = 0;
        foreach (Arr::wrap($columns) as $column) {
            $counter++;
            $query .= sprintf('%s %s', $column, $operator);
            if ($counter < count(Arr::wrap($columns))) {
                $query .= sprintf(' %s ', mb_strtoupper($boolean));
            }
        }

        $this->whereQuery = $query;

        return $this;
    }

    public function whereNotNull(string|array $columns, $boolean = 'and'): self
    {
        return $this->whereNull($columns, $boolean, true);
    }

    public function limitBy(int $limit): self
    {
        $this->limitByQuery = sprintf('LIMIT %s ', $limit);

        return $this;
    }

    public function withQuery(string $as, Closure $callback): self
    {
        $this->withQueries = array_merge($this->withQueries, [$as . ' AS (' . call_user_func($callback) . ')']);

        return $this;
    }
}
