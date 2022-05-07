<?php

namespace NorbyBaru\AwsTimestream;

use Aws\Credentials\Credentials;
use Aws\TimestreamQuery\TimestreamQueryClient;
use Aws\TimestreamWrite\TimestreamWriteClient;

class TimestreamManager
{
    private TimestreamQueryClient $reader;

    private TimestreamWriteClient $writer;

    public function __construct(
        ?string $key,
        ?string $secret,
        ?string $profile,
        string $version = 'latest',
        string $region = 'eu-west-1'
    ) {
        $config = [
            'version' => $version,
            'region' => $region,
        ];

        // key and secret can be omitted when lambda or container policy allow access to AWS Timestream Service
        if ($key && $secret) {
            $config['credentials'] = new Credentials($key, $secret);
        }

        if ($profile) {
            $config['profile'] = $profile;
        }

        $this->reader = new TimestreamQueryClient($config);
        $this->writer = new TimestreamWriteClient($config);
    }

    public function getReader(): TimestreamQueryClient
    {
        return $this->reader;
    }

    public function getWriter(): TimestreamWriteClient
    {
        return $this->writer;
    }
}
