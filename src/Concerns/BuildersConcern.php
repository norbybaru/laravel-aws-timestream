<?php

namespace NorbyBaru\AwsTimestream\Concerns;

use Illuminate\Support\Str;
use NorbyBaru\AwsTimestream\Builder\Builder;

trait BuildersConcern
{
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

    public function getGroupByQuery(): string
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
            $queryString = $withQueries;
            $queryString .= ' ';
            $queryString .= $this->getSelectStatement();
        } else {
            $queryString = $this->getSelectStatement();
        }

        if ($this->getFromQuery()) {
            $queryString .= ' ';
            $queryString .= $this->getFromQuery();
        }

        if ($this->getWhereQuery()) {
            $queryString .= ' ';
            $queryString .= $this->getWhereQuery();
        }

        if ($this->getGroupByQuery()) {
            $queryString .= ' ';
            $queryString .= $this->getGroupByQuery();
        }

        if ($this->getOrderByQuery()) {
            $queryString .= ' ';
            $queryString .= $this->getOrderByQuery();
        }

        if ($this->getLimitByQuery()) {
            $queryString .= ' ';
            $queryString .= $this->getLimitByQuery();
        }

        return $queryString;
    }

    private function strTrimFrom(string $string, string $part): string
    {
        return mb_substr($string, mb_strlen($part));
    }

    public function mergeQuery(Builder $queryBuilder)
    {
        if ($withQueries = $queryBuilder->getWithQueries()) {
            $this->withQueries = $withQueries;
        }

        if ($select = $queryBuilder->getSelectStatement()) {
            $this->selectStatement = $select;
        }

        if ($queryBuilder->getWhereQuery()) {
            $query = $queryBuilder->getWhereQuery();
            if ($this->getWhereQuery()) {
                $query = sprintf(' AND %s', trim($this->strTrimFrom($queryBuilder->getWhereQuery(), 'WHERE')));
            }

            $this->whereQuery .= $query;
        }

        if ($fromQuery = $queryBuilder->getFromQuery()) {
            $this->fromQuery = $fromQuery;
        }

        if ($orderBy = $queryBuilder->getOrderByQuery()) {
            $this->orderByQuery = $orderBy;
        }
    }
}
