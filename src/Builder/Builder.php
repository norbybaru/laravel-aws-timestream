<?php

namespace Ringierimu\AwsTimestream\Builder;

use Illuminate\Support\Str;
use Ringierimu\AwsTimestream\Concerns\BuildersConcern;
use Ringierimu\AwsTimestream\Contract\QueryBuilderContract;

abstract class Builder implements QueryBuilderContract
{
    use BuildersConcern;

    private string $queryString = '';
    protected string $database = '';
    protected string $table = '';
    protected string $fromQuery = '';
    protected string $whereQuery = '';
    protected string $selectStatement = '';
    protected string $orderByQuery = '';
    protected string $groupByQuery = '';
    protected string $limitByQuery = '';
    protected array $withQueries = [];

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
        return '"' . $this->getDatabase() . '"."' . $this->getTable() . '"';
    }

    public function getSql(): string
    {
        return $this->toSql();
    }

    public function toSql(): string
    {
        return $this->getQueryString();
    }

    public function getFromQuery(): string
    {
        return $this->fromQuery;
    }

    public function getSelectStatement(): string
    {
        return $this->selectStatement;
    }

    public function getWhereQuery(): string
    {
        return $this->whereQuery;
    }

    public function getOrderByQuery(): string
    {
        return $this->orderByQuery;
    }

    public function getGroupByQuery()
    {
        return $this->groupByQuery;
    }

    public function getLimitByQuery(): string
    {
        return $this->limitByQuery;
    }

    public function getWithQueries(): array
    {
        return $this->withQueries;
    }

    public function getQueryString(): string
    {
        if ($this->getWithQueries()) {
            $withQueries = 'WITH ' . implode(',', $this->getWithQueries());
            $queryString = Str::of($withQueries)
                ->append(' ')
                ->append($this->getSelectStatement());
        } else {
            $queryString = Str::of($this->getSelectStatement());
        }

        if ($this->getFromQuery()) {
            $queryString = $queryString
                ->append(' ')
                ->append($this->getFromQuery());
        }

        if ($this->getWhereQuery()) {
            $queryString = $queryString
                ->append(' ')
                ->append($this->getWhereQuery());
        }

        if ($this->getGroupByQuery()) {
            $queryString = $queryString
                ->append(' ')
                ->append($this->getGroupByQuery());
        }

        if ($this->getOrderByQuery()) {
            $queryString = $queryString
                ->append(' ')
                ->append($this->getOrderByQuery());
        }

        if ($this->getLimitByQuery()) {
            $queryString = $queryString
                ->append(' ')
                ->append($this->getLimitByQuery());
        }

        return $queryString;
    }

    private function strTrimFrom(string $string, string $part): string
    {
        return mb_substr($string, mb_strlen($part));
    }
}
