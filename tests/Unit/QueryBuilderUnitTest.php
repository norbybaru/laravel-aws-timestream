<?php

namespace Ringierimu\AwsTimestream\Tests\Unit;

use Ringierimu\AwsTimestream\Query\TimestreamQueryBuilder;
use Ringierimu\AwsTimestream\Tests\TestCase;

class QueryBuilderUnitTest extends TestCase
{
    public function test_it_can_return_query_builder()
    {
        $this->assertInstanceOf(TimestreamQueryBuilder::class, TimestreamQueryBuilder::query());
    }

    public function test_it_can_build_where_between_query()
    {
        $option1 = $this->faker->randomDigit;
        $option2 = $this->faker->randomDigit;
        $query = TimestreamQueryBuilder::query()
            ->whereBetween('id', [$option1, $option2]);

        $this->validateSql("WHERE id BETWEEN {$option1} and {$option2}", $query);
    }

    public function test_it_can_build_where_not_between_query()
    {
        $option1 = $this->faker->randomDigit;
        $option2 = $this->faker->randomDigit;
        $query = TimestreamQueryBuilder::query()
            ->whereNotBetween('id', [$option1, $option2]);

        $this->validateSql("WHERE id NOT BETWEEN {$option1} and {$option2}", $query);
    }

    public function test_it_can_build_where_is_null_query()
    {
        $query = TimestreamQueryBuilder::query()
            ->whereNull('id');

        $this->validateSql("WHERE id IS NULL", $query);
    }

    public function test_it_can_build_multiple_column_with_is_null_query()
    {
        $query = TimestreamQueryBuilder::query()
            ->whereNull(['id', 'user', 'date']);

        $this->validateSql("WHERE id IS NULL AND user IS NULL AND date IS NULL", $query);
    }

    public function test_it_can_build_where_is_not_null_query()
    {
        $query = TimestreamQueryBuilder::query()
            ->whereNotNull('id');

        $this->validateSql("WHERE id IS NOT NULL", $query);
    }

    public function test_it_can_build_multiple_column_with_is_not_null_query()
    {
        $query = TimestreamQueryBuilder::query()
            ->whereNotNull(['id', 'user', 'date']);

        $this->validateSql("WHERE id IS NOT NULL AND user IS NOT NULL AND date IS NOT NULL", $query);
    }

    public function test_it_can_build_where_in_query()
    {
        $query = TimestreamQueryBuilder::query()
            ->whereIn('state', ['open', 'draft', 'published']);

        $this->validateSql("WHERE state IN ('open','draft','published')", $query);
    }

    private function validateSql(string $expected, TimestreamQueryBuilder $builder)
    {
        $this->assertEquals($expected, trim($builder->getSql()));
    }
}
