<?php

namespace RZP\Services;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class DruidService
{
    const ACCEPT_HEADER    = 'Accept';
    const APPLICATION_JSON = 'application/json';
    const CONTENT_TYPE     = 'Content-Type';
    const REQUEST_TIMEOUT  = 75; // timeout in seconds

    protected $trace;
    protected $app;
    protected $config;
    protected $url;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app    = $app;

        $this->trace  = $app['trace'];

        $this->config = $app['config']->get('services.druid');

        $this->url    = $this->config['url'];
    }


    public function getDataFromDruid(array $content)
    {
        $method = 'POST';

        $headers[self::ACCEPT_HEADER] = self::APPLICATION_JSON;

        $headers[self::CONTENT_TYPE] = self::APPLICATION_JSON;

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth' => [
                $this->config['auth']['key'],
                $this->config['auth']['secret']
            ],
        ];

        try
        {
            $response = Requests::request(
                $this->url,
                $headers,
                json_encode($content),
                $method,
                $options);


        } catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::DRUID_REQUEST_FAILURE, [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'trace'   => $e->getTrace(),
            ]);

            return [$e->getMessage(), null];
        }

        $data = json_decode($response->body, true);

        $errorMessage = isset($data['errorMessage']) ? $data['errorMessage'] : null;

        if ($response->status_code !== 200)
        {
            $this->trace->error(TraceCode::DRUID_REQUEST_FAILURE, [
                'message' => $errorMessage,
            ]);
        }

        return [$errorMessage, $data];
    }
}
