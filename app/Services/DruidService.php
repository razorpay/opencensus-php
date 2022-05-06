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
    const REQUEST_TIMEOUT  = 10; // timeout in seconds

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


    public function getDataFromDruid(array $content, $timeout = self::REQUEST_TIMEOUT)
    {
        $method = 'POST';

        $headers[self::ACCEPT_HEADER] = self::APPLICATION_JSON;

        $headers[self::CONTENT_TYPE] = self::APPLICATION_JSON;

        $options = [
            'timeout' => $timeout,
            'auth' => [
                $this->config['auth']['key'],
                $this->config['auth']['secret']
            ],
        ];

        $start_time = microtime(true);

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
           $this->trace->info(TraceCode::DRUID_RESPONSE_TIME, [
               'response_time' => microtime(true) - $start_time
           ]);

           $errorMessage = $e->getMessage();

            $this->trace->error(TraceCode::DRUID_REQUEST_FAILURE, [
                'message' => $errorMessage,
                'code'    => $e->getCode(),
            ]);

            return [$errorMessage, null];
        }

        $this->trace->info(TraceCode::DRUID_RESPONSE_TIME, [
            'response_time' => microtime(true) - $start_time
        ]);

        $data = json_decode($response->body, true);

        $errorMessage = isset($data['errorMessage']) ? $data['errorMessage'] : null;

        if ($response->status_code !== 200)
        {
            $this->trace->error(TraceCode::DRUID_REQUEST_FAILURE, [
                'data'          => $data,
                'error_message' => $errorMessage,
                'response_code' => $response->status_code,
            ]);
        }

        return [$errorMessage, $data];
    }
}
