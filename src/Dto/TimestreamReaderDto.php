<?php

namespace NorbyBaru\AwsTimestream\Dto;

use NorbyBaru\AwsTimestream\Builder\Builder;

final class TimestreamReaderDto extends AbstractTimestreamDto
{
    private ?int $maxRows = null;

    private string $nextTokenToContinueReading = '';

    public function __construct(protected Builder $builder, string $forTable = null)
    {
        $this->database = config('timestream.database');
        $this->tables = config('timestream.tables.aliases');

        if ($forTable) {
            $this->forTable($forTable);
        }
    }

    public static function make(Builder $builder, string $forTable = null): self
    {
        return new self($builder, $forTable);
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
        $params = [
            'QueryString' => $this->getQueryString(),
        ];

        if ($this->maxRows) {
            $params['MaxRows'] = $this->maxRows;
        }

        // we can pass an initial next token to proceed previous queries
        if ($this->nextTokenToContinueReading !== '') {
            $params['NextToken'] = $this->nextTokenToContinueReading;
        }

        return $params;
    }

    /**
     * @param int|null $maxRows
     *
     * @return TimestreamReaderDto
     */
    public function setMaximumRowLimit(?int $maxRows): TimestreamReaderDto
    {
        $this->maxRows = $maxRows;

        return $this;
    }

    /**
     * @param string $nextTokenToContinueReading
     *
     * @return TimestreamReaderDto
     */
    public function setNextTokenToContinueReading(string $nextTokenToContinueReading): TimestreamReaderDto
    {
        $this->nextTokenToContinueReading = $nextTokenToContinueReading;

        return $this;
    }
}
