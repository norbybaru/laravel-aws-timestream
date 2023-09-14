<?php

namespace NorbyBaru\AwsTimestream\Tests\Unit;

use NorbyBaru\AwsTimestream\Builder\Builder;
use NorbyBaru\AwsTimestream\Dto\TimestreamReaderDto;
use NorbyBaru\AwsTimestream\Tests\TestCase;
use NorbyBaru\AwsTimestream\TimestreamBuilder;

class ReaderUnitTest extends TestCase
{
    public function test_reader_dto_should_return_correct_structure()
    {
        $queryBuilder = TimestreamBuilder::query();
        $dto = TimestreamReaderDto::make($queryBuilder, 'test');

        $this->assertInstanceOf(TimestreamReaderDto::class, $dto);
        $this->assertIsArray($dto->toArray());
        $this->assertEquals('QueryString', array_keys($dto->toArray())[0]);
    }

    public function test_query_builder_should_return_query_string()
    {
        $sql = "SELECT * FROM \"database-name\".\"table-name\" WHERE time >= ago(24h) AND measure_value::varchar NOT IN ('reviewer','open','closed') ORDER BY time desc";
        $queryBuilder = TimestreamBuilder::query()
            ->select('*')
            ->from("database-name", 'table-name')
            ->whereAgo('time', '24h', '>=')
            ->whereNotIn('measure_value::varchar', ['reviewer', 'open', 'closed'])
            ->orderBy('time', 'desc');

        $this->assertInstanceOf(Builder::class, $queryBuilder);
        $this->assertIsString($queryBuilder->getSql());
        $this->assertEquals($queryBuilder->getSql(), $sql);
    }

    public function test_query_builder_can_alias_tables()
    {
        $sql = "SELECT * FROM \"database-name\".\"table-name\" AS t1 WHERE time >= ago(24h) AND measure_value::varchar NOT IN ('reviewer','open','closed') ORDER BY time desc";
        $queryBuilder = TimestreamBuilder::query()
            ->select('*')
            ->from("database-name", 'table-name', 't1')
            ->whereAgo('time', '24h', '>=')
            ->whereNotIn('measure_value::varchar', ['reviewer', 'open', 'closed'])
            ->orderBy('time', 'desc');

        $this->assertInstanceOf(Builder::class, $queryBuilder);
        $this->assertIsString($queryBuilder->getSql());
        $this->assertEquals($queryBuilder->getSql(), $sql);
    }

    public function test_query_builder_can_left_join_tables()
    {
        $sql = "SELECT * FROM \"database-name\".\"table-name\" AS t1 LEFT JOIN \"database-name\".\"table-name2\" AS t2 ON t1.id = t2.id WHERE time >= ago(24h) AND measure_value::varchar NOT IN ('reviewer','open','closed') ORDER BY time desc";
        $queryBuilder = TimestreamBuilder::query()
            ->select('*')
            ->from("database-name", 'table-name', 't1')
            ->leftJoin("database-name", 'table-name2', 't2', 't1.id = t2.id')
            ->whereAgo('time', '24h', '>=')
            ->whereNotIn('measure_value::varchar', ['reviewer', 'open', 'closed'])
            ->orderBy('time', 'desc');

        $this->assertInstanceOf(Builder::class, $queryBuilder);
        $this->assertIsString($queryBuilder->getSql());
        $this->assertEquals($queryBuilder->getSql(), $sql);
    }

    public function test_query_builder_can_use_aliases_in_selects()
    {
        $sql = "SELECT p.name, avg(r.rating) AS avg_rating FROM \"shop\".\"products\" AS p LEFT JOIN \"shop\".\"reviews\" AS r ON p.id = r.product_id ORDER BY time desc LIMIT 10";
        $queryBuilder = TimestreamBuilder::query()
            ->select('p.name, avg(r.rating) AS avg_rating')
            ->from("shop", 'products', 'p')
            ->leftJoin("shop", 'reviews', 'r', 'p.id = r.product_id')
            ->orderBy('time', 'desc')
            ->limitBy(10);

        $this->assertInstanceOf(Builder::class, $queryBuilder);
        $this->assertIsString($queryBuilder->getSql());
        $this->assertEquals($queryBuilder->getSql(), $sql);
    }

    public function test_query_builder_can_add_rawWhere()
    {
        $sql = "SELECT p.name, avg(r.rating) AS avg_rating FROM \"shop\".\"products\" AS p LEFT JOIN \"shop\".\"reviews\" AS r ON p.id = r.product_id WHERE p.name = \"test\" AND avg_rating > 4 GROUP BY p.name ORDER BY time desc LIMIT 10";
        $queryBuilder = TimestreamBuilder::query()
            ->select('p.name, avg(r.rating) AS avg_rating')
            ->from("shop", 'products', 'p')
            ->leftJoin("shop", 'reviews', 'r', 'p.id = r.product_id')
            ->whereRaw("WHERE p.name = \"test\"")
            ->andWhere('avg_rating', 4, '>')
            ->orderBy('time', 'desc')
            ->groupBy('p.name')
            ->limitBy(10);

        $this->assertInstanceOf(Builder::class, $queryBuilder);
        $this->assertIsString($queryBuilder->getSql());
        $this->assertEquals($queryBuilder->getSql(), $sql);
    }

    public function test_query_build_can_add_having()
    {
        $sql = "SELECT p.name, avg(r.rating) AS avg_rating FROM \"shop\".\"products\" AS p LEFT JOIN \"shop\".\"reviews\" AS r ON p.id = r.product_id WHERE p.name = \"test\" GROUP BY p.name HAVING avg(r.rating) > 4 ORDER BY time desc LIMIT 10";
        $queryBuilder = TimestreamBuilder::query()
            ->select('p.name, avg(r.rating) AS avg_rating')
            ->from("shop", 'products', 'p')
            ->leftJoin("shop", 'reviews', 'r', 'p.id = r.product_id')
            ->whereRaw("WHERE p.name = \"test\"")
            ->orderBy('time', 'desc')
            ->groupBy('p.name')
            ->having('avg(r.rating)', 4, '>')
            ->limitBy(10);

        $this->assertInstanceOf(Builder::class, $queryBuilder);
        $this->assertIsString($queryBuilder->getSql());
        $this->assertEquals($queryBuilder->getSql(), $sql);
    }

    public function test_query_build_can_add_rawhaving()
    {
        $sql = "SELECT p.name, avg(r.rating) AS avg_rating FROM \"shop\".\"products\" AS p LEFT JOIN \"shop\".\"reviews\" AS r ON p.id = r.product_id WHERE p.name = \"test\" GROUP BY p.name HAVING avg(r.rating) > 4 ORDER BY time desc LIMIT 10";
        $queryBuilder = TimestreamBuilder::query()
            ->select('p.name, avg(r.rating) AS avg_rating')
            ->from("shop", 'products', 'p')
            ->leftJoin("shop", 'reviews', 'r', 'p.id = r.product_id')
            ->whereRaw("WHERE p.name = \"test\"")
            ->orderBy('time', 'desc')
            ->groupBy('p.name')
            ->havingRaw('HAVING avg(r.rating) > 4')
            ->limitBy(10);

        $this->assertInstanceOf(Builder::class, $queryBuilder);
        $this->assertIsString($queryBuilder->getSql());
        $this->assertEquals($queryBuilder->getSql(), $sql);
    }

    public function test_reader_dto_should_return_correct_query_string_from_query_builder()
    {
        $queryBuilder = TimestreamBuilder::query()
            ->select('*')
            ->from("database-name", 'table-name')
            ->whereAgo('time', '24h', '>=')
            ->whereIn('measure_value::varchar', ['reviewer', 'open', 'closed'])
            ->orderBy('time', 'desc');

        $dto = TimestreamReaderDto::make($queryBuilder);
        $this->assertEquals($queryBuilder->getSql(), $dto->toArray()['QueryString']);
    }

    public function test_reader_dto_can_inject_database_and_table_for_sql_query()
    {
        $queryBuilder = TimestreamBuilder::query()
            ->select('*')
            ->whereAgo('time', '24h', '>=')
            ->whereIn('measure_value::varchar', ['reviewer', 'open', 'closed'])
            ->orderBy('time', 'desc');

        $alias = 'test';
        $dto = TimestreamReaderDto::make($queryBuilder, $alias);

        $database = config('timestream.database');
        $table = config("timestream.tables.aliases.{$alias}");
        $this->assertStringContainsString("FROM \"{$database}\".\"{$table}\"", $dto->toArray()['QueryString']);
    }
}
