<?php

namespace NorbyBaru\AwsTimestream\Tests\Unit;

use Illuminate\Support\Carbon;
use NorbyBaru\AwsTimestream\Builder\Builder;
use NorbyBaru\AwsTimestream\Builder\TimestreamQueryBuilder;
use NorbyBaru\AwsTimestream\Tests\TestCase;
use NorbyBaru\AwsTimestream\TimestreamBuilder;

class QueryBuilderUnitTest extends TestCase
{
    protected function setUp(): void
    {
        TimestreamQueryBuilder::macro(
            "whereDateBetween",
            fn ($from, $to) => $this->whereBetween('date', [$from, $to])
        );

        TimestreamQueryBuilder::macro(
            "whereHoursAgo",
            fn (string $column, int $hours, string $operator = '=') => $this->whereAgo($column, "{$hours}h", $operator)
        );

        parent::setUp();
    }

    public function test_it_can_return_query_builder()
    {
        $this->assertInstanceOf(Builder::class, TimestreamBuilder::query());
    }

    public function test_it_can_build_where_between_query()
    {
        $option1 = $this->faker->randomDigit;
        $option2 = $this->faker->randomDigit;
        $query = TimestreamBuilder::query()
            ->whereBetween('id', [$option1, $option2]);

        $this->validateSql("WHERE id BETWEEN {$option1} and {$option2}", $query);
    }

    public function test_it_can_build_where_not_between_query()
    {
        $option1 = $this->faker->randomDigit;
        $option2 = $this->faker->randomDigit;
        $query = TimestreamBuilder::query()
            ->whereNotBetween('id', [$option1, $option2]);

        $this->validateSql("WHERE id NOT BETWEEN {$option1} and {$option2}", $query);
    }

    public function test_it_can_build_where_is_null_query()
    {
        $query = TimestreamBuilder::query()
            ->whereNull('id');

        $this->validateSql("WHERE id IS NULL", $query);
    }

    public function test_it_can_build_multiple_column_with_is_null_query()
    {
        $query = TimestreamBuilder::query()
            ->whereNull(['id', 'user', 'date']);

        $this->validateSql("WHERE id IS NULL AND user IS NULL AND date IS NULL", $query);
    }

    public function test_it_can_build_where_is_not_null_query()
    {
        $query = TimestreamBuilder::query()
            ->whereNotNull('id');

        $this->validateSql("WHERE id IS NOT NULL", $query);
    }

    public function test_it_can_build_multiple_column_with_is_not_null_query()
    {
        $query = TimestreamBuilder::query()
            ->whereNotNull(['id', 'user', 'date']);

        $this->validateSql("WHERE id IS NOT NULL AND user IS NOT NULL AND date IS NOT NULL", $query);
    }

    public function test_it_can_build_where_in_query()
    {
        $query = TimestreamBuilder::query()
            ->whereIn('state', ['open', 'draft', 'published']);

        $this->validateSql("WHERE state IN ('open','draft','published')", $query);
    }

    public function test_it_should_build_query_from_marco()
    {
        $dateFrom = Carbon::now()->yesterday()->toDateString();
        $dateTo = Carbon::now()->toDateString();
        $query = TimestreamBuilder::query()
            ->whereIn('state', ['open', 'draft', 'published'])
            ->whereDateBetween($dateFrom, $dateTo)
            ->whereHoursAgo('time', 72);

        $this->validateSql(
            "WHERE state IN ('open','draft','published') AND date BETWEEN {$dateFrom} and {$dateTo} AND time = ago(72h)",
            $query
        );
    }

    private function validateSql(string $expected, TimestreamQueryBuilder $builder)
    {
        $this->assertEquals($expected, trim($builder->getSql()));
    }
}
