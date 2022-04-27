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
     * Database name
     */
    'database' => env('AWS_TIMESTREAM_DATABASE'),

    /**
     * Enable query and metedata logging on server
     */
    'debug_query' => env('TIMESTREAM_DEBUG_QUERY', false),

    /**
     * Contains list of tables to access your Timestream database
     */
    'tables' => [
        /**
         * Default table table name
         */
        'default' => null,

        /**
         * To handle multiple tables acess, you can map them below and access them.
         * The `value` of the `key` should represent the table name that you want to access
         * and they `key`  can be anything meaningful.
         * eg. ['listing' => 'listing-kpi', ['github' => 'ingestion-github']]
         */
        'sources' => [],
    ],
];
