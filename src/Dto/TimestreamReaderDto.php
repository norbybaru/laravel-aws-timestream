<?php

namespace Ringierimu\AwsTimestream\Dto;

use Ringierimu\AwsTimestream\Builder\Builder;

class TimestreamReaderDto extends AbstractTimestreamDto
{
    public function __construct(protected Builder $builder, string $forTable = null)
    {
        $this->database = config('timestream.database');
        $this->tables = config('timestream.tables.sources');

        if ($forTable) {
            $this->forTable($forTable);
        }
    }

    public static function make(Builder $builder, string $forTable = null): self
    {
        return new static($builder, $forTable);
    }

    public function getQuery(): Builder
    {
        return $this->builder;
    }

    protected function getQueryString(): string
    {
        if (!$this->builder->getFromQuery()) {
            $this->builder->from($this->database, $this->table);
        }

        return $this->builder->getSql();
    }

    public function toArray(): array
    {
        return [
            'QueryString' => $this->getQueryString(),
        ];
    }
}
