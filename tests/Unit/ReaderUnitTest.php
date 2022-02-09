<?php

namespace Ringierimu\AwsTimestream\Tests\Unit;

use Ringierimu\AwsTimestream\Dto\TimestreamReaderDto;
use Ringierimu\AwsTimestream\Query\TimestreamQueryBuilder;
use Ringierimu\AwsTimestream\Tests\TestCase;

class ReaderUnitTest extends TestCase
{
    public function test_reader_dto_should_return_correct_structure()
    {
        $queryBuilder = TimestreamQueryBuilder::query();
        $dto = TimestreamReaderDto::make($queryBuilder, 'test');

        $this->assertInstanceOf(TimestreamReaderDto::class, $dto);
        $this->assertIsArray($dto->toArray());
        $this->assertEquals('QueryString', array_keys($dto->toArray())[0]);
    }

    public function test_query_builder_should_return_query_string()
    {
        $sql = "SELECT * FROM \"database-name\".\"table-name\" WHERE time >= ago(24h) AND measure_value::varchar NOT IN ('reviewer','open','closed') ORDER BY time desc";
        $queryBuilder = TimestreamQueryBuilder::query()
            ->select('*')
            ->from("database-name", 'table-name')
            ->whereAgo('time', '24h', '>=')
            ->whereNotIn('measure_value::varchar', ['reviewer', 'open', 'closed'])
            ->orderBy('time', 'desc');

        $this->assertInstanceOf(TimestreamQueryBuilder::class, $queryBuilder);
        $this->assertIsString($queryBuilder->getSql());
        $this->assertEquals($queryBuilder->getSql(), $sql);
    }

    public function test_reader_dto_should_return_correct_query_string_from_query_builder()
    {
        $queryBuilder = TimestreamQueryBuilder::query()
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
        $queryBuilder = TimestreamQueryBuilder::query()
            ->select('*')
            ->whereAgo('time', '24h', '>=')
            ->whereIn('measure_value::varchar', ['reviewer', 'open', 'closed'])
            ->orderBy('time', 'desc');

        $tableSource = 'test';
        $dto = TimestreamReaderDto::make($queryBuilder, $tableSource);

        $database = config('timestream.database');
        $table = config("timestream.tables.sources.{$tableSource}");
        $this->assertStringContainsString("FROM \"{$database}\".\"{$table}\"", $dto->toArray()['QueryString']);
    }
}
