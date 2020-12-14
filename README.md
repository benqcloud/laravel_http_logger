# Log HTTP requests and headers

This package provides a middleware to log incoming http requests data (body data and headers).

## Installation

You can install the package via composer:

```bash
composer config secure-http false
composer config repositories.laravel_http_logger git http://dcc0server.benq.corp.com:30000/doc/Laravel_http_logger
composer require benq/laravel_http_logger
```

Optionally you can publish the configfile with:

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
