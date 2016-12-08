<?php

namespace RZP\Services;

use Requests;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Exchange
{
    const REQUEST_TIMEOUT = 30;

    protected $baseUrl;

    protected $appId;

    protected $secret;

    protected $config;

    protected $trace;

    protected $proxy;

    protected $mode;

    const URLS = [
        'current' => 'latest.json',
    ];

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.exchange');

        $this->baseUrl = $this->config['url'];

        $this->mode = $app['rzp.mode'];

        $this->appId = $this->config['appId'];

        $this->proxy = $app['config']->get('gateway.proxy_address');
    }

    public function latest($base)
    {
        $url = self::URLS[__FUNCTION__];

        $input = [
            'app_id' => $this->appId,
            'base'   => $base
        ];

        $response = $this->sendRequest($url, 'get', $input);

        return $response;
    }

    protected function sendRequest($url, $method, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
        {
            $data = '';
        }

        $headers['Accept'] = 'application/json';

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
//            'proxy' => $this->proxy
        );

        $request = array(
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendExchangeRequest($request);

        $this->trace->info(TraceCode::EXCHANGE_RESPONSE, [
            'response' => $response->body
        ]);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::EXCHANGE_RESPONSE, $decodedResponse);

        $this->checkErrors($decodedResponse);

        return $decodedResponse;
    }

    protected function sendExchangeRequest($request)
    {
        $this->trace->info(TraceCode::EXCHANGE_REQUEST, $request);

        $method = $request['method'];

        try
        {
            $response = Requests::$method(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['options']);
        }
        catch(\Requests_Exception $e)
        {
            throw $e;
        }

        return $response;
    }
}
