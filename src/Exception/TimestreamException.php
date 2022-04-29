<?php

namespace Ringierimu\AwsTimestream\Exception;

use Exception;

abstract class TimestreamException extends Exception
{
    public function __construct(Exception $e, protected array $context = [])
    {
        parent::__construct($e->getMessage(), $e->getCode(), $e->getPrevious());
    }

    /**
     * Get the exception's context information.
     *
     * @return array
     */
    public function context()
    {
        return $this->context;
    }
}
