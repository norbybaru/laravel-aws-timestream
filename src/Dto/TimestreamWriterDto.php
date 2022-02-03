<?php

namespace Ringierimu\LaravelAwsTimestream\Dto;

class TimestreamWriterDto extends AbstractTimestreamDto
{
    public function __construct(protected array $records, protected array $commonAttributes = [], string $forTable = null)
    {
        $this->database = config('timestream.database');
        $this->tables = config('timestream.tables.sources');

        if ($forTable) {
            $this->forTable($forTable);
        }
    }

    public static function make(array $recodrs, array $commonAttributes = [], string $forTable = null): self
    {
        return new static($recodrs, $commonAttributes, $forTable);
    }

    public function toArray(): array
    {
        $data = [
            'DatabaseName' => $this->database,
            'Records' => $this->records,
            'TableName' => $this->table,
        ];

        if ($this->commonAttributes) {
            $data['CommonAttributes'] = $this->commonAttributes;
        }

        return $data;
    }
}
