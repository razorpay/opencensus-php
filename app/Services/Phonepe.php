<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class Phonepe
{
    protected $app;

    protected $config;

    protected $trace;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.gateway_downtime.phonepe');
    }

    protected function buildRequest()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-VERIFY' => $this->config['secret'] . '###3',
        ];

        $options = [
            'timeout' => 1,
        ];

        $request = [
            'url' => $this->config['url'],
            'headers' => $headers,
            'options' => $options,
        ];

        return $request;
    }

    public function sendRequest($a)
    {
        $request = $this->buildRequest();

        try
        {
            $response = Requests::GET(
                $request['url'],
                $request['headers'],
                $request['options']
            );
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $this->trace->error(
                TraceCode::PHONEPE_DOWNTIME_FETCH_ERROR,
                [
                    'data'    => $e->getData()
                ]);
            throw $e;
        }


        return json_decode($response->body, true);
    }
}
