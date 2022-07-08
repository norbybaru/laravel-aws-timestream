<?php

namespace NorbyBaru\AwsTimestream\Dto;

final class TimestreamWriterDto extends AbstractTimestreamDto
{
    public function __construct(protected array $records, protected array $commonAttributes = [], string $forTable = null)
    {
        $this->database = config('timestream.database');
        $this->tables = config('timestream.tables.aliases');

        if ($forTable) {
            $this->forTable($forTable);
        }
    }

    public static function make(array $records, array $commonAttributes = [], string $forTable = null): self
    {
        return new self($records, $commonAttributes, $forTable);
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
