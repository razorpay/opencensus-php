<?php

namespace RZP\Services;

use Requests;
use Requests_Session;

class SuccessRateService
{
    const CONTENT_TYPE_HEADER      = 'Content-Type';
    const ACCEPT_HEADER            = 'Accept';
    const APPLICATION_JSON         = 'application/json';

    const REQUEST_TIMEOUT = 20;
    const MAX_RETRY_COUNT = 3;

    protected $app;

    protected $config;

    protected $trace;

    protected $request;

    public function _construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.doppler');

        if ($this->request === null)
        {
            $this->request = $this->initRequestObject();
        }
    }

    protected function initRequestObject()
    {
        $baseUrl = $this->getUrl();

        $defaultHeaders = $this->getDefaultHeaders();

        $defaultOptions = $this->getDefaultOptions();

        $request = new Requests_Session($baseUrl, $defaultHeaders, [], $defaultOptions);

        return $request;
    }

    protected function getUrl(): string
    {
        return $this->config['url'];
    }

    protected function getDefaultHeaders(): array
    {
        $headers = [
            self::CONTENT_TYPE_HEADER      => self::APPLICATION_JSON,
            self::ACCEPT_HEADER            => self::APPLICATION_JSON,
        ];

        return $headers;
    }

    protected function getDefaultOptions(): array
    {
        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
        ];

        return $options;
    }

    public function sendRequest(string $method, string $path, string $content)
    {
        $url = $this->getUrl();

    }

}
