<?php

namespace Benq\Logger\Middleware;

use Log;

class HttpLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        return $next($request);
    }

    /**
     * Handle Log on terminated.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response  $response
     */
    public function terminate($request, $response)
    {
        $this->httpLog($request, $response);
    }

    /**
     * Handle HTTP log.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response  $response
     */
    public function httpLog($request, $response)
    {
        $now = microtime(true);

        $log = [
            'method' => $request->method(),
            'path' => $request->path(),
            'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',
            'larave_interval' => defined('LARAVEL_START') ? round($now - LARAVEL_START, 3) : -1,
            'request_start_time' => isset($_SERVER['REQUEST_TIME']) ? date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) : null,
            'request_interval' => isset($_SERVER['REQUEST_TIME']) ? ($now - $_SERVER['REQUEST_TIME']) : null,
            'request_query' => str_replace($request->url(), '', $request->fullUrl()),
            'request_body' => $this->processContext($request, 'content'),
            'request_headers' => $this->processContext($request, 'headers'),
            'response_status' => $response->getStatusCode(),
            'response_message' => (!$response->headers->contains('Content-Type', 'text/html')) ? $this->getResponseLimit($response) : null,
            'response_filtered' => ($response->headers->contains('Content-Type', 'application/json')) ? $this->processContext($response, 'response') : null
        ];

        if ($log['response_status'] < 500) {
            Log::info('http_log', $log);
        } else {
            Log::error('http_log', $log);
        }
    }

    /**
     * Process context.
     *
     * @param array $data
     * @param string $name data source
     * @return array
     */
    protected function processContext($data, $name): array
    {
        $config = config("http-logger.{$name}");

        if (false !== $config) {
            $context = $this->{'get' . ucfirst($name)}($data);

            if (array_get($config, 'except')) {
                return array_except($context, array_get($config, 'except'));
            }

            if (array_get($config, 'only')) {
                return array_only($context, array_get($config, 'only'));
            }

            return $context;
        }

        return [];
    }

    /**
     * Get request content.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function getContent($request): array
    {
        return $request->getContent() ? json_decode($request->getContent(), true) : [];
    }

    /**
     * Get request headers.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function getHeaders($request): array
    {
        return $request->header();
    }

    /**
     * Get response.
     *
     * @param \Illuminate\Http\Response $response
     * @return array
     */
    protected function getResponse($response): array
    {
        return $response->content() ? json_decode($response->content(), true) : [];
    }

    /**
     * Get response.
     *
     * @param \Illuminate\Http\Response $response
     * @return string
     */
    protected function getResponseLimit($response): string
    {
        if (false !== config("http-logger.response-limit")) {
            return substr($response->content(), 0, config("http-logger.response-limit"));
        }

        return $response->content();
    }
}
