<?php

return [
    /**
     * Key to authorized access
     */
    'key' => env('AWS_TIMESTREAM_KEY'),

    /**
     * Secret to authorized access
     */
    'secret' => env('AWS_TIMESTREAM_SECRET'),

    /**
     * (string) Allows you to specify which profile to us
     *
     * Note: Specifying "profile" will cause the "credentials" key to be ignored.
     */
    'profile' => env('AWS_TIMESTREAM_PROFILE'),

    /**
     * AWS region for your Timestream. Default to eu-west-1
     */
    'region' => env('AWS_TIMESTREAM_REGION', 'eu-west-1'),

    /**
     * Database name
     */
    'database' => env('AWS_TIMESTREAM_DATABASE'),

    /**
     * Enable query and metadata logging on server
     */
    'debug_query' => env('TIMESTREAM_DEBUG_QUERY', false),

    /**
     * Contains list of tables to access your Timestream database
     */
    'tables' => [
        /**
         * To handle multiple tables access, you can map them below using key value pair.
         * The `value`  should represent the table name that you want to access
         * The `key` is an alias to the table name that can be anything meaningful.
         * eg. ['listing' => 'listing-kpi', 'github' => 'ingestion-github', 'prod' => 'prod']
         */
        'aliases' => [],
    ],
];
