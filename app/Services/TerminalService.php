<?php

namespace RZP\Services;

use RZP\Trace\TraceCode;
use RZP\Exception\IntegrationException;

class TerminalService
{
    protected $app;

    protected $config;

    protected $mode;

    protected $trace;

    protected $baseUrl;


    const URL               = 'url';
    const CONTENT           = 'content';
    const EXCEPTION         = 'exception';
    const STATUS_CODE       = 'status_code';

    public function __construct($app, string $mode = '')
    {
        $this->app = $app;

        $this->config = $this->app['config']->get('applications.terminals_service');

        $this->trace = $this->app['trace'];

        $this->mode = $mode ?? $this->app['rzp.mode'];

        $this->baseUrl = $this->getBaseUrl($this->mode);
    }

    protected function sendRequest(string $path, array $content, string $method = Requests::POST): \Requests_Response
    {
        $url = $this->baseUrl . $path;

        $headers = $this->getHeaders();

        $options = $this->getOptions();

        try
        {
            $this->trace->info(TraceCode::TERMINALS_SERVICE_REQUEST,
                [
                    self::URL       => $url,
                ]);

            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );

            $this->trace->info(TraceCode::TERMINALS_SERVICE_RESPONSE,
                [
                   self::STATUS_CODE => $response->status_code,
                ]);

            if ($response->status_code >= 400)
            {
                throw new IntegrationException('Terminals service request failed with status code : ' . $response->status_code);
            }
        }
        catch (\Exception $exception)
        {
            $data = [
                self::EXCEPTION => $exception->getMessage(),
                self::URL       => $url,
            ];

            $this->trace->error(TraceCode::TERMINALS_SERVICE_INTEGRATION_ERROR, $data);

            throw $exception;
        }
    }

    protected function getBaseUrl(string $mode)
    {
        $urlConfig = 'applications.terminals_service.' . $mode . '.url';

        return $this->app['config']->get($urlConfig);
    }
}
