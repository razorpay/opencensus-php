<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
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
        'latest'  => "latest.json",
        'convert' => "convert"
    ];

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.exchange');

        $this->baseUrl = $this->config['url'];

        $this->mode = $app['rzp.mode'];

        $this->appId = $this->config['appId'];

        $this->proxy = $app['config']->get('app.proxy_address');

        $this->proxyEnabled = $app['config']->get('app.proxy_enabled');

    }

    public function latest($base)
    {
        $url = self::URLS[__FUNCTION__];

        $input = [
            'app_id' => $this->appId,
            'base'   => $base
        ];

        $response = $this->sendRequest($url, 'GET', $input);

        return $response['rates'];
    }

    public function convert($value, $from, $to)
    {
        $url = self::URLS[__FUNCTION__] . "/$value/$from/$to";

        $input = [
            'app_id' => $this->appId
        ];

        $response = $this->sendRequest($url, 'GET', $input);

        return $response['response'];
    }

    protected function sendRequest($url, $method, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
        {
            $data = '';
        }

        $headers['Accept'] = 'application/json';

        $options['timeout'] = self::REQUEST_TIMEOUT;

        if ($this->proxyEnabled === true)
        {
            $options['proxy'] = $this->proxy;
        }

        $request = array(
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendExchangeRequest($request);

        $this->trace->info(TraceCode::EXCHANGE_RESPONSE,
            [
                'response' => $response->body
            ]);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::EXCHANGE_RESPONSE, $decodedResponse);

        return $decodedResponse;
    }

    protected function sendExchangeRequest($request)
    {
        $this->trace->info(TraceCode::EXCHANGE_REQUEST, $request);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);
        }
        catch(\WpOrg\Requests\Exception $e)
        {
            throw $e;
        }

        return $response;
    }
}
