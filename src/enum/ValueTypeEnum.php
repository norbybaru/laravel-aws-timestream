<?php

namespace NorbyBaru\AwsTimestream\Enum;

enum ValueTypeEnum: string
{
    case DOUBLE = 'DOUBLE';
    case BIGINT = 'BIGINT';
    case VARCHAR = 'VARCHAR';
    case BOOLEAN = 'BOOLEAN';
    case TIMESTAMP = 'TIMESTAMP';
}