# Log HTTP requests and headers

This package provides a middleware to log incoming http requests data (body data and headers).

## Installation

You can install the package via composer:

```bash
composer require benq/laravel_http_logger
```

Optionally you can publish the config file with:

```bash
php artisan vendor:publish --provider="Benq\Logger\Providers\LoggerServiceProvider"
```

This is the contents of the published config file:

```php

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

```

## Formatter

If project have to use custom formatter, you can use Benq\Logger\Formatter\JsonFormatter via config/loggin.php

```php
return [
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ],

        'stdout' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'with' => [
                'stream' => 'php://stdout',
            ],
            'formatter' => Benq\Logger\Formatter\JsonFormatter,
            'formatter_with' => [
                'setMaxNormalizeDepth' => 2
            ],
            'level' => 'debug',
        ],

```
