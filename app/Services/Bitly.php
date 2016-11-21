<?php

namespace RZP\Services;

use Requests;
use RZP\Trace\TraceCode;

class Bitly
{
    const SHORTEN = 'shorten';
    const SHORTEN_SSL = 'shorten_ssl';

    const URLS = [
        self::SHORTEN => 'http://api.bit.ly/v3/shorten',
        self::SHORTEN_SSL => 'https://api-ssl.bitly.com/v3/shorten'
    ];

    public function __construct($app)
    {
        // $this->mode = $app['rzp.mode'];

        $this->trace = $app['trace'];

        // $this->request = $app['request'];

        $this->config = $app['config']->get('applications.bitly');

        // $this->clientId = $this->config['client_id'];
        // $this->clientSecret = $this->config['client_secret'];
        $this->accessToken = $this->config['access_token'];
    }

    public function shortenUrl($longUrl)
    {
        $endpoint = self::URLS[self::SHORTEN_SSL];

        $requestParams = $this->getRequestParams($longUrl);

        $response = Requests::post($endpoint, [], $requestParams);

        $responseBody = $response->body;

        $formattedResponse = json_decode($responseBody, true);

        $traceData = [
            'response_body'         => $responseBody,
            'formatted_response'    => $formattedResponse,
            'long_url'              => $longUrl,
            'endpoint'              => $endpoint,
        ];

        if (isset($formattedResponse['data']['url']) === false)
        {
            $this->trace->error(
                TraceCode::INVOICE_BITLY_FAIL,
                $traceData
            );

            return $longUrl;
        }

        $this->trace->info(
            TraceCode::INVOICE_BITLY_RESPONSE,
            $traceData
        );

        return $formattedResponse['data']['url'];
    }

    protected function getRequestParams($longUrl)
    {
        $credentials = $this->getCredentials();

        $params = [
            'uri' => $longUrl,
            'format' => 'json',
        ];

        $requestParams = array_merge($credentials, $params);

        return $requestParams;
    }

    protected function getCredentials()
    {
        return [
            'access_token' => $this->accessToken
        ];

        // return [
        //     'login' => $this->clientId,
        //     'apiKey' => $this->clientSecret,
        // ];
    }
}
