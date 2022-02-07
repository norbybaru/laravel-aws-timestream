<?php

namespace Ringierimu\LaravelAwsTimestream\Query\Trait;

use Closure;
use Illuminate\Support\Str;

trait CanUseTimestreamQuery
{
    private string $queryString = '';
    private string $fromQuery = '';
    private string $whereQuery = '';
    private string $selectStatement = '';
    private string $orderByQuery = '';
    private string $groupByQuery = '';
    private array $withQueries = [];

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getConnection(): string
    {
        return '"' . $this->databse . '"."' . $this->table . '"';
    }

    public function selectRaw(string $statetement): self
    {
        $this->selectStatement = $statetement;

        return $this;
    }

    public function select(string $columns): self
    {
        $this->selectStatement = sprintf('SELECT %s', $columns);

        return $this;
    }

    public function from(string $database, string $table, string $alias = null): self
    {
        $this->fromQuery = 'FROM "' . $database . '"."' . $table . '"';

        if ($alias) {
            $this->fromQuery = Str::of($this->fromQuery)->append(" {$alias}");
        }

        return $this;
    }

    public function fromRaw(string $statetement): self
    {
        $this->fromQuery = $statetement;

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

    /**
     * @param  mixed  $value
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $boolean
     * @param  bool  $ago
     */
    public function where(string $column, $value, string $operator = '=', string $boolean = 'and', bool $ago = false): self
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
    public function whereAgo(string $column, $value, string $operator = '=', string $boolean = 'and'): self
    {
        return $this->where($column, $value, $operator, $boolean, true);
    }

    /**
     * @param  mixed  $value
     * @param  string  $column
     * @param  string  $operator
     */
    public function andWhere(string $column, $value, string $operator = '='): self
    {
        return $this->where($column, $value, $operator);
    }

    public function whereIn(string $column, array|\Closure $values, string $operator = null, string $boolean = 'and'): self
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

        $operator = trim(mb_strtoupper($operator) . ' IN');
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

    public function whereNotIn(string $column, array|\Closure $values): self
    {
        return $this->whereIn($column, $values, 'not');
    }

    public function limitBy(string $limit): self
    {
        Str::of($this->queryString)
            ->append(sprintf(' LIMIT %s ', $limit));

        return $this;
    }

    public function withQuery(string $as, Closure $callback): self
    {
        $this->withQueries = array_merge($this->withQueries, [$as . ' AS (' . call_user_func($callback) . ')']);

        return $this;
    }
}
