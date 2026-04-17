<?php

namespace NorbyBaru\AwsTimestream;

use Aws\Credentials\Credentials;
use Aws\TimestreamQuery\TimestreamQueryClient;
use Aws\TimestreamWrite\TimestreamWriteClient;

class TimestreamManager
{
    private ?TimestreamQueryClient $reader = null;

    private ?TimestreamWriteClient $writer = null;

    private array $config;

    public function __construct(
        ?string $key,
        ?string $secret,
        ?string $profile,
        string $region,
        string $version = 'latest',
    ) {
        $this->config = [
            'version' => $version,
            'region' => $region,
        ];

        // key and secret can be omitted when lambda or container policy allow access to AWS Timestream Service
        if ($key && $secret) {
            $this->config['credentials'] = new Credentials($key, $secret);
        }

        if ($profile) {
            $this->config['profile'] = $profile;
        }
    }

    public function getReader(): TimestreamQueryClient
    {
        if ($this->reader === null) {
            $this->reader = $this->createReader();
        }

        return $this->reader;
    }

    public function getWriter(): TimestreamWriteClient
    {
        if ($this->writer === null) {
            $this->writer = $this->createWriter();
        }

        return $this->writer;
    }

    private function createReader(): TimestreamQueryClient
    {
        return new TimestreamQueryClient($this->config);
    }

    private function createWriter(): TimestreamWriteClient
    {
        return new TimestreamWriteClient($this->config);
    }
}
