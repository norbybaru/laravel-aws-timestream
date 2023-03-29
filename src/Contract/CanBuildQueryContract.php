<?php

namespace NorbyBaru\AwsTimestream\Contract;

use NorbyBaru\AwsTimestream\Builder\Builder;

interface CanBuildQueryContract
{
    public function __construct();

    /**
     * Build SQL query
     */
    public function builder(): Builder;
}
