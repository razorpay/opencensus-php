<?php

namespace RZP\Services;

use Requests;

use RZP\Trace\TraceCode;
use RZP\Exception\IntegrationException;

class TerminalsService
{
    protected $app;

    protected $config;

    protected $mode;

    protected $trace;

    protected $baseUrl;


    const URL               = 'url';
    const CONTENT           = 'content';
    const CONTENT_TYPE      = 'content_type';
    const EXCEPTION         = 'exception';
    const STATUS_CODE       = 'status_code';

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $this->app['trace'];

        $this->mode = $this->app['rzp.mode'];
    }

    protected function sendRequest(string $path, array $content, string $method = Requests::POST): \Requests_Response
    {
        $url = $this->getBaseUrl($this->mode) . $path;

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

            return $response;
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

    protected function getBaseUrl()
    {
        $urlConfig = 'applications.terminals_service.' . $this->mode . '.url';

        return $this->app['config']->get($urlConfig);
    }

    protected function getHeaders()
    {
        return [
            self::CONTENT_TYPE      => 'Application/json',
        ];
    }

    protected function getOptions()
    {
        $auth = [
            'api',
            $this->getPassword(),

        ];

        return [
            'auth' => $auth
        ];
    }

    protected function getPassword()
    {
        $passwordConfig = 'applications.terminals_service.' . $this->mode . '.password';

        return $this->app['config']->get($passwordConfig);

    }
}
