<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;

class Phonepe
{
    protected $app;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

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

        $response = Requests::GET(
            $request['url'],
            $request['headers'],
            $request['options']
        );

        return json_decode($response->body, true);
    }
}
