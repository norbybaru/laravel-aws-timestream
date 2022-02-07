<?php

namespace Ringierimu\AwsTimestream\Dto;

abstract class AbstractTimestreamDto
{
    protected string $database;

    protected string $table;

    protected array $tables;

    public function onDataBase(string $databaseName): self
    {
        $this->database = $databaseName;

        return $this;
    }

    public function forTable(string $source): self
    {
        $this->table = $this->tables[$source];

        return $this;
    }

    abstract public function toArray(): array;
}
