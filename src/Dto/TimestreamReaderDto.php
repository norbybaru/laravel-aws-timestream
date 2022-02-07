<?php

namespace Ringierimu\AwsTimestream\Dto;

use Ringierimu\AwsTimestream\Query\TimestreamQuery;

class TimestreamReaderDto extends AbstractTimestreamDto
{
    public function __construct(protected TimestreamQuery $query, string $forTable = null)
    {
        $this->database = config('timestream.database');
        $this->tables = config('timestream.tables.sources');

        if ($forTable) {
            $this->forTable($forTable);
        }
    }

    public static function make(TimestreamQuery $query, string $forTable = null): self
    {
        return new static($query, $forTable);
    }

    public function getQuery(): TimestreamQuery
    {
        return $this->query;
    }

    protected function getQueryString(): string
    {
        if (!$this->query->getFromQuery()) {
            $this->query->from($this->database, $this->table);
        }

        return $this->query->getSql();
    }

    public function toArray(): array
    {
        return [
            'QueryString' => $this->getQueryString(),
        ];
    }
}
