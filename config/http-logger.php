<?php

return [
    /*
     * false - don't log body fields
     * ['only'] - log fields only
     * ['except'] - don't log fields
     *
     * If ['only'] is set, ['except'] parameter will be omitted
     */
    // 'content' => false,
    'content' => [
        'only' => [],
        'except' => ['password'],
    ],

    /*
     * false - don't log headers
     * ['only'] - log headers only
     * ['except'] - don't log headers
     *
     * If ['only'] is set, ['except'] parameter will be omitted
     */
    // 'headers' => false,
    'headers' => [
        'only' => [],
        'except' => [],
    ],

    /*
     * false - don't log response
     * ['only'] - log response only
     * ['except'] - don't log response
     *
     * If ['only'] is set, ['except'] parameter will be omitted
     */
    // 'response' => false,
    'response' => [
        'only' => [],
        'except' => [],
    ],

    /*
     * false - don't limit response
     * limit - response message limit
     *
     */
    // 'response-limit' => false,
    'response-limit' => 10000
];
