<?php

namespace NorbyBaru\AwsTimestream\Tests\Unit;

use NorbyBaru\AwsTimestream\Contract\QueryBuilderContract;
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

        $this->assertInstanceOf(QueryBuilderContract::class, $queryBuilder);
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
