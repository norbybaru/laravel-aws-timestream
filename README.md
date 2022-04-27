# Laravel AWS Timestream

[![Tests](https://github.com/RingierIMU/laravel-aws-timestream/actions/workflows/run-tests.yml/badge.svg)](https://github.com/RingierIMU/laravel-aws-timestream/actions/workflows/run-tests.yml)

AWS Timestream is a fast, scalable, and serverless time series database service.
This package is an opinionated implementation to query timestream and ingest data into timestream.

It provides a query builder class which has common timeseries sql function. This was inspired by Laravel Eloquent ORM. 
See supported query functions `Ringierimu\AwsTimestream\Contract\QueryBuilderContract`

It also provide a payload builder class to format your data correctly to ingest into timestream. 
See `Ringierimu\AwsTimestream\Contract\PayloadBuilderContract`

## Install
```bash
composer require ringierimu/laravel-aws-timestream
```

## Configuration
- Publish config
```bash
php artisan vendor:publish --provider="Ringierimu\AwsTimestream\TimestreamServiceProvider" --tag="timestream-config"
```
- Open `timestream.php` config file and setup your databse name and tables
- Setup you AWS Timestream keys and permissions with the following enviroment variable
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

use Ringierimu\AwsTimestream\TimestreamService;
use Ringierimu\AwsTimestream\TimestreamBuilder;
use Ringierimu\AwsTimestream\Dto\TimestreamReaderDto;

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

use Ringierimu\AwsTimestream\TimestreamService;
use Ringierimu\AwsTimestream\TimestreamBuilder;
use Ringierimu\AwsTimestream\Dto\TimestreamReaderDto;

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
We need to build our payload that Timestream will accept for ingestion.

1. Use `TimestreamBuilder` to build ingestion payload
```php
<?php

use Ringierimu\AwsTimestream\TimestreamService;
use Ringierimu\AwsTimestream\Dto\TimestreamWriterDto;
use Ringierimu\AwsTimestream\TimestreamBuilder;

public function ingest(TimestreamService $timestreamService)
{
    $metrics = [
        'measure_name' => 'cpu_usage',
        'measure_value' => 80,
        'time' => Carbon::now(),
        'dimensions' => [
            'mac_address' => 'randomstring',
            'ref' => 'refs',
        ],
    ];

    $payload = TimestreamBuilder::payload(
        $metrics['measure_name'],
        $metrics['measure_value'],
        $metrics['time'],
        'VARCHAR',
        $metrics['dimensions'],
    )->toArray();

    $timestreamWriter = TimestreamWriterDto::make($payload)->forTable('table-name');
    return $timestreamService->write($timestreamWriter);
}
```

2. Ingestion data in batch using Common Attributes to reduce ingestion cost with Timestream

```php
<?php

use Ringierimu\AwsTimestream\TimestreamService;
use Ringierimu\AwsTimestream\Dto\TimestreamWriterDto;
use Ringierimu\AwsTimestream\Support\TimestreamPayloadBuilder;

public function ingest(TimestreamService $timestreamService)
{
    $metrics = [
        [
            'measure_name' => 'cpu_usage',
            'measure_value' => 80,
            'time' => Carbon::now(),
            'dimensions' => [
                'ref' => 'ref_1',
            ],
        ],
        [
            'measure_name' => 'memory_usage',
            'measure_value' => 20,
            'time' => Carbon::now(),
            'dimensions' => [
                'ref' => 'ref_2',
            ],
        ]
    ];

    $commonAttributes['device_name'] = 'device_1';
    $commonAttributes['mac_address'] = 'randomstring';

    $payload = TimestreamBuilder::batchPayload($metrics);

    $common = TimestreamBuilder::commonAttributes($commonAttributes);

    $timestreamWriter = TimestreamWriterDto::make($payload, $common, 'table-name');
    return $timestreamService->write($timestreamWriter);
}
```

# Online Resources
- https://docs.aws.amazon.com/timestream/latest/developerguide/what-is-timestream.html

