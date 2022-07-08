<?php

namespace NorbyBaru\AwsTimestream\Dto;

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

    public function forTable(string $alias): self
    {
        $this->table = $this->tables[$alias];

        return $this;
    }

    abstract public function toArray(): array;
}
