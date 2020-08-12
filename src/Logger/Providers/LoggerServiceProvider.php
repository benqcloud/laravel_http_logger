<?php

namespace Benq\Logger\Providers;

use Benq\Logger\Middleware\HttpLogger;
use Illuminate\Support\ServiceProvider;

class LoggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../../config/http-logger.php' => config_path('http-logger.php')
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/http-logger.php',
            'http-logger'
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');
        $kernel->pushMiddleware(HttpLogger::class);
    }
}
