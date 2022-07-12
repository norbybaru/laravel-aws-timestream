<?php

namespace NorbyBaru\AwsTimestream\Contract;

interface CanBuildQueryContract
{
     /**
     * Build SQL query
     *
     * @return void
     */
    public function builder(): void;
}
