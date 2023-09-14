<?php

namespace NorbyBaru\AwsTimestream\Builder;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use NorbyBaru\AwsTimestream\Concerns\BuildersConcern;
use NorbyBaru\AwsTimestream\Contract\QueryBuilderContract;

abstract class Builder implements QueryBuilderContract
{
    use BuildersConcern;

    public const JOIN_TYPE_LEFT = 'LEFT';
    public const JOIN_TYPE_RIGHT = 'RIGHT';
    public const JOIN_TYPE_INNER = 'INNER';
    public const JOIN_TYPE_FULL = 'FULL';
    public const JOIN_TYPE_LEFT_OUTER = 'LEFT OUTER';
    public const JOIN_TYPE_RIGHT_OUTER = 'RIGHT OUTER';
    public const JOIN_TYPE_FULL_OUTER = 'FULL OUTER';

    public const JOIN_TYPES = [
        self::JOIN_TYPE_LEFT,
        self::JOIN_TYPE_RIGHT,
        self::JOIN_TYPE_INNER,
        self::JOIN_TYPE_FULL,
        self::JOIN_TYPE_LEFT_OUTER,
        self::JOIN_TYPE_RIGHT_OUTER,
        self::JOIN_TYPE_FULL_OUTER,
    ];

    protected string $database = '';
    protected string $table = '';
    protected string $fromQuery = '';
    protected string $whereQuery = '';
    protected string $havingQuery = '';
    protected string $selectStatement = '';
    protected string $orderByQuery = '';
    protected string $groupByQuery = '';
    protected string $limitByQuery = '';
    protected array $withQueries = [];
    protected array $joinQueries = [];

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
            $this->fromQuery = Str::of($this->fromQuery)->append(" AS {$alias}");
        }

        return $this;
    }

    public function join(string $database, string $table, string $type = 'LEFT', ?string $alias = null, ?string $on = null): Builder
    {
        if (!in_array($type, self::JOIN_TYPES)) {
            throw new \InvalidArgumentException(sprintf('Invalid join type %s', $type));
        }
        $joinQuery = $type . ' JOIN "' . $database . '"."' . $table . '"';
        if ($alias) {
            $joinQuery = Str::of($joinQuery)->append(" AS {$alias}");
        }

        if ($on) {
            $joinQuery = Str::of($joinQuery)->append(" ON {$on}");
        }
        $this->joinQueries = array_merge($this->joinQueries, [$joinQuery]);

        return $this;
    }

    public function leftJoin(string $database, string $table, string $alias = null, string $on = null): self
    {
        return $this->join($database, $table, self::JOIN_TYPE_LEFT, $alias, $on);
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

    public function whereRaw(string $statement): self
    {
        $this->whereQuery = $statement;

        return $this;
    }

    public function where(string $column, $value, string $operator = '=', string $boolean = 'and', bool $ago = false): self
    {
        $query = Str::of($this->whereQuery);
        $this->whereQuery = $this->modifyQueryPart('WHERE', $query, $column, $value, $operator, $boolean, $ago);

        return $this;
    }

    protected function modifyQueryPart(
        string $sqlPart,
        Stringable $query,
        string $column,
        $value,
        string $operator = '=',
        string $boolean = 'and',
        bool $ago = false
    ): Stringable {
        if (!in_array($sqlPart, ['WHERE', 'HAVING'])) {
            throw new \InvalidArgumentException(sprintf('Invalid sql part %s', $sqlPart));
        }
        $value = $value instanceof Closure
            // If the value is a Closure, it means the developer is performing an entire
            ? '(' . call_user_func($value) . ')'
            : $value;

        if ($query->length() == 0) {
            $queryPart = $query->append(
                sprintf($sqlPart . ' %s %s %s', $column, $operator, $value)
            );

            if ($ago) {
                $queryPart = $query->append(
                    sprintf($sqlPart . ' %s %s ago(%s)', $column, $operator, $value)
                );
            }

            return $queryPart;
        }

        $queryPart = $query->append(
            sprintf(' %s %s %s %s', mb_strtoupper($boolean), $column, $operator, $value)
        );

        if ($ago) {
            $queryPart = $query->append(
                sprintf(' %s %s %s ago(%s)', mb_strtoupper($boolean), $column, $operator, $value)
            );
        }

        return $queryPart;
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

    public function whereNotIn(string $column, array|Closure $values, string $boolean = 'and'): self
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function whereBetween(string $column, array $values, $boolean = 'and', $not = false): self
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

    public function whereNotNull(string|array $columns, $boolean = 'and'): self
    {
        return $this->whereNull($columns, $boolean, true);
    }

    public function havingRaw(string $statement): self
    {
        $this->havingQuery = $statement;

        return $this;
    }

    public function having(string $column, $value, string $operator = '=', string $boolean = 'and', bool $ago = false): self
    {
        $query = Str::of($this->havingQuery);
        $this->havingQuery = $this->modifyQueryPart('HAVING', $query, $column, $value, $operator, $boolean, $ago);

        return $this;
    }

    public function limitBy(int $limit): self
    {
        $this->limitByQuery = sprintf('LIMIT %s', $limit);

        return $this;
    }

    public function withQuery(string $as, Closure $callback): self
    {
        $this->withQueries = array_merge($this->withQueries, [$as . ' AS (' . call_user_func($callback) . ')']);

        return $this;
    }
}
