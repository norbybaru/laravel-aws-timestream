<?php

namespace NorbyBaru\AwsTimestream;

use Aws\Result;
use Aws\TimestreamQuery\Exception\TimestreamQueryException;
use Aws\TimestreamQuery\TimestreamQueryClient;
use Aws\TimestreamWrite\Exception\TimestreamWriteException;
use Aws\TimestreamWrite\TimestreamWriteClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NorbyBaru\AwsTimestream\Dto\TimestreamReaderDto;
use NorbyBaru\AwsTimestream\Dto\TimestreamWriterDto;
use NorbyBaru\AwsTimestream\Exception\FailTimestreamQueryException;
use NorbyBaru\AwsTimestream\Exception\FailTimestreamWriterException;
use NorbyBaru\AwsTimestream\Exception\UnknownTimestreamDataTypeException;

class TimestreamService
{
    public TimestreamQueryClient $reader;

    /**
     * @return string|null
     */
    public function getNextToken(): ?string
    {
        return $this->nextToken;
    }

    public TimestreamWriteClient $writer;

    private ?string $nextToken = null;

    public function __construct(TimestreamManager $manager)
    {
        $this->reader = $manager->getReader();
        $this->writer = $manager->getWriter();
    }

    public function batchWrite(TimestreamWriterDto $timestreamWriter): \Aws\Result
    {
        return $this->ingest($timestreamWriter->toArray());
    }

    public function write(TimestreamWriterDto $timestreamReader): \Aws\Result
    {
        return $this->ingest($timestreamReader->toArray());
    }

    private function ingest(array $payload): \Aws\Result
    {
        try {
            $result = $this->writer->writeRecords($payload);
        } catch (TimestreamWriteException $e) {
            $records = $payload['Records'];
            if ($e->getAwsErrorCode() === 'RejectedRecordsException') {
                $records = collect($e->get('RejectedRecords'))
                    ->map(function ($data) use ($records) {
                        return [
                            'RecordIndex' => $data['RecordIndex'],
                            'Record' => $records[$data['RecordIndex']],
                            'Reason' => $data['Reason'],
                        ];
                    })->all();
            }

            throw new FailTimestreamWriterException($e, $records);
        }

        if (($status = Arr::get($result->get('@metadata') ?? [], 'statusCode')) != 200) {
            Log::debug('Failed To insert Timestream', $payload);

            throw new FailTimestreamWriterException($status);
        }

        return $result;
    }

    public function query(TimestreamReaderDto $timestreamReader): Collection
    {
        $params = $timestreamReader->toArray();

        return $this->runQuery($params, $params['MaxRows'] ?? PHP_INT_MAX);
    }

    private function runQuery($params, int $rowsLeft): Collection
    {
        if ($rowsLeft <= 0) {
            return collect();
        }

        try {
            if ($this->shouldDebugQuery()) {
                Log::debug('=== Timestream Query ===', $params);
            }

            $result = $this->reader->query($params);
            $this->nextToken = $result->get('NextToken');
            if ($this->nextToken !== null) {
                $parsedRows = $this->parseQueryResult($result);
                $rowsLeft -= $parsedRows->count();
                $params['NextToken'] = $this->nextToken;
                // we fetch everything recursively until the limit has been reached or there is no more data
                return $this->runQuery($params, $rowsLeft)->merge($parsedRows);
            }
        } catch (TimestreamQueryException $e) {
            throw new FailTimestreamQueryException($e, $params);
        }

        return $this->parseQueryResult($result);
    }

    private function parseQueryResult(Result $result): Collection
    {
        if ($this->shouldDebugQuery()) {
            Log::debug('=== Query status === ', $result->get('QueryStatus'));
        }

        $columnInfo = $result->get('ColumnInfo');

        if ($this->shouldDebugQuery()) {
            Log::debug('=== Query Metadata === ', $columnInfo);
        }

        return collect($result->get('Rows'))
            ->map(fn ($row) => $this->parseRow($row, $columnInfo));
    }

    private function parseRow(array $row, array $columnInfo): array
    {
        $rowFormatted = [];
        foreach ($row['Data'] as $key => $value) {
            $formattedKey = Str::beforeLast(Arr::get($columnInfo, "{$key}.Name"), '::');

            if (Arr::get($rowFormatted, $formattedKey)) {
                continue;
            }

            $rowFormatted[$formattedKey] = $this->dataType(
                Arr::get(
                    $columnInfo,
                    "{$key}.Type.ScalarType"
                ),
                Arr::get($value, 'ScalarValue', null)
            );
        }

        return $rowFormatted;
    }

    protected function dataType(string $type, $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $return = match ($type) {
            'BIGINT' => (int) $value,
            'VARCHAR' => (string) $value,
            'DOUBLE' => (float) $value,
            'TIMESTAMP' => Carbon::createFromFormat('Y-m-d H:i:s.u000', $value),
            default => throw new UnknownTimestreamDataTypeException('Unknown Data Type From TimeStream: ' . $type),
        };

        return $return;
    }

    private function shouldDebugQuery(): bool
    {
        return config('timestream.debug_query', false);
    }
}
