<?php

namespace Ringierimu\AwsTimestream\Contract;

use Closure;

interface QueryBuilderContract
{
    public function getDatabase(): ?string;

    public function getTable(): ?string;

    public function getConnection(): string;

    public function selectRaw(string $statetement): self;

    public function select(string $columns): self;

    public function from(string $database, string $table, string $alias = null): self;

    public function fromRaw(string $statetement): self;

    public function orderBy(string $column, string $direction = 'asc'): self;

    public function groupBy($args): self;

    public function where(string $column, $value, string $operator = '=', string $boolean = 'and', bool $ago = false): self;

    public function whereAgo(string $column, $value, string $operator = '=', string $boolean = 'and'): self;

    public function andWhere(string $column, $value, string $operator = '='): self;

    public function whereIn(string $column, array|Closure $values, string $boolean = 'and', $not = false): self;

    public function whereNotIn(string $column, array|Closure $values, string $boolean = 'and'): self;

    public function whereBetween(string $column, array $values, $boolean = 'and', $not = false): self;

    public function whereNotBetween(string $column, array $values, $boolean = 'and'): self;

    public function whereNull(string|array $columns, $boolean = 'and', $not = false): self;

    public function whereNotNull(string|array $columns, $boolean = 'and'): self;

    public function limitBy(int $limit): self;

    public function withQuery(string $as, Closure $callback): self;

    public function getSql(): string;

    public static function query(): self;

    public function mergeQuery(QueryBuilderContract $builder);
}
