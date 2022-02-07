<?php

namespace Ringierimu\AwsTimestream\Contract;

use Closure;

interface TimestreamQueryContract
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

    public function whereIn(string $column, array|Closure $values, string $operator = null, string $boolean = 'and'): self;

    public function whereNotIn(string $column, array|Closure $values): self;

    public function limitBy(string $limit): self;

    public function withQuery(string $as, Closure $callback): self;
}
