# Laravel AWS Timestream

[![Run Unit Tests](https://github.com/norbybaru/laravel-aws-timestream/actions/workflows/run-tests.yml/badge.svg)](https://github.com/norbybaru/laravel-aws-timestream/actions/workflows/run-tests.yml)
[![PHPStan](https://github.com/norbybaru/laravel-aws-timestream/actions/workflows/phpstan.yml/badge.svg)](https://github.com/norbybaru/laravel-aws-timestream/actions/workflows/phpstan.yml)

AWS Timestream is a fast, scalable, and serverless time series database service.
This package is an opinionated implementation to query timestream and ingest data into timestream.

## Upgrading from version `0.2.x`

Please not that version `0.3.x` is still backward compatible with version `0.2.x`. 
No breaking changes introduced, however i will suggest you slowly use the new Payload builder approach for Timestream ingestion as from version `0.4.x` we shall drop support for legacy builder.

See updated examples below start using new approach with `TimestreamPayloadBuilder`.

## Install
```bash
composer require norbybaru/laravel-aws-timestream
```

## Configuration
- Publish config
```bash
php artisan vendor:publish --provider="NorbyBaru\AwsTimestream\TimestreamServiceProvider" --tag="timestream-config"
```
- Open `timestream.php` config file and setup your database name and tables
- Setup you AWS Timestream keys and permissions with the following environment variable
```
AWS_TIMESTREAM_KEY=
AWS_TIMESTREAM_SECRET=
AWS_TIMESTREAM_PROFILE=
```

## Basic Usage
### Query Timestream
Using `TimestreamBuilder::query()` will give autocomplete of all available functions

1. Using `TimestreamBuilder` to build query to be passed onto `TimestreamReaderDto` which generate am object that can be consumed by `TimestreamService` query function

```php
<?php

use NorbyBaru\AwsTimestream\TimestreamService;
use NorbyBaru\AwsTimestream\TimestreamBuilder;
use NorbyBaru\AwsTimestream\Dto\TimestreamReaderDto;

public function overview(TimestreamService $timestreamService)
{
    $queryBuilder = TimestreamBuilder::query()
        ->select('*')
        ->from("database-name", 'table-name')
        ->whereAgo('time', '24h', '>=')
        ->whereNotIn('measure_value::varchar', ['reviewer', 'open', 'closed'])
        ->orderBy('time', 'desc');
    
    TimestreamReaderDto::make($queryBuilder);

    // response from Aws timestream
    return $timestreamService->query($timestreamReader)
}
```

2. Use `TimestreamReaderDto` to inject `from` query with default `database` name and on demand `table` name. **NB.** No need to add `->from()` query on your query builder.
 ```php
<?php

use NorbyBaru\AwsTimestream\TimestreamService;
use NorbyBaru\AwsTimestream\TimestreamBuilder;
use NorbyBaru\AwsTimestream\Dto\TimestreamReaderDto;

public function overview(TimestreamService $timestreamService)
{
    $queryBuilder = TimestreamBuilder::query()
        ->select('*')
        ->whereAgo('time', '24h', '>=')
        ->whereNotIn('measure_value::varchar', ['reviewer', 'open', 'closed'])
        ->orderBy('time', 'desc');
    
    TimestreamReaderDto::make($queryBuilder, 'table-name');

    // response from Aws timestream
    return $timestreamService->query($timestreamReader)
}
```
### Timestream Ingestion
We need to build our ingestion payload that Timestream will accept for ingestion.
To achieve that we can use the `TimestreamPayloadBuilder` class to build a payload that Aws Timestream will understand

#### Example
1. Build single record ingestion payload
```php
<?php

use NorbyBaru\AwsTimestream\TimestreamService;
use NorbyBaru\AwsTimestream\Dto\TimestreamWriterDto;
use NorbyBaru\AwsTimestream\Builder\TimestreamPayloadBuilder;

public function ingest(TimestreamService $timestreamService)
{
    $payload = TimestreamPayloadBuilder::make(measureName: 'cpu_usage')
        ->setMeasureValue(value: 80.5)
        ->setMeasureValueType(type: ValueTypeEnum::DOUBLE())
        ->setDimensions(name: "mac_address", value: '00:11:22:AA:BB:CC ')
        ->setDimensions(name: "ref", value: 'station a')
        ->setTime(Carbon::now());

    $timestreamWriter = TimestreamWriterDto::make($payload->toRecords())->forTable('table-name');

    return $timestreamService->write($timestreamWriter);
}
```

2. Ingestion data in batch using Common Attributes to reduce ingestion cost with Timestream

```php
<?php

use NorbyBaru\AwsTimestream\TimestreamService;
use NorbyBaru\AwsTimestream\Dto\TimestreamWriterDto;
use NorbyBaru\AwsTimestream\Builder\TimestreamPayloadBuilder;

public function ingest(TimestreamService $timestreamService)
{
    $payloads = [
        ...TimestreamPayloadBuilder::make(measureName: 'cpu_usage')
            ->setMeasureValue(value: 80.6)
            ->setDimensions(name: "ref", value: 'station a')
            ->toRecords(),
        ...TimestreamPayloadBuilder::make(measureName: 'memory_usage')
            ->setMeasureValue(value: 45.5)
            ->setDimensions(name: "ref", value: 'station b')
            ->toRecords(),
    ];

    $common = CommonPayloadBuilder::make()
        ->setCommonDimensions(name: 'processor', value: 'unix')
        ->setCommonDimensions(name: 'mac_address', value: '00:11:22:AA:BB:CC')
        ->setCommonDimensions(name: 'device_name', value: 'device_1')
        ->setCommonMeasureValueType(ValueTypeEnum::DOUBLE())
        ->setCommonTime(Carbon::now())
        ->toArray();

    $timestreamWriter = TimestreamWriterDto::make($payloads, $common, 'table-name');

    return $timestreamService->write($timestreamWriter);
}
```

# Run Unit Test
```bash
composer test
```

# Online Resources
- https://docs.aws.amazon.com/timestream/latest/developerguide/what-is-timestream.html
- https://docs.aws.amazon.com/timestream/latest/developerguide/writes.html
- https://docs.aws.amazon.com/timestream/latest/developerguide/queries.html

