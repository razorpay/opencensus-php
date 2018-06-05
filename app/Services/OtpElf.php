<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;

class OtpElf
{
    protected $baseUrl;

    protected $config;

    protected $trace;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.otpelf');

        $this->apiKey = $this->config['api_key'];

        $this->baseUrl = $this->config['url'];
    }

    public function otpSend(array $input)
    {
        $response = $this->sendRequest('/', 'POST', $input);

        return $response;
    }

    public function otpResend(array $input)
    {
        $content = [

        ];

        $response = $this->sendRequest('/act', 'POST', $content);

        return $response;
    }

    public function otpSubmit(array $input)
    {
        $content = [
            'payment_id' => $input['payment_id'],
            'request'    => [
                'action' => 'submit_otp',
                'data'   => [
                    'otp'    => $input['gateway']['otp']
                ]
            ]
        ];

        $response = $this->sendRequest('/act', 'POST', $content);

        return $response;
    }

    public function sendRequest($url, $method, $content = null)
    {
        $url = $this->baseUrl . $url;

        if ($content === null)
        {
            $content = '';
        }

        $headers = [
            'Authorization' => 'Bearer ',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $options = [
            'timeout' => 20
        ];

        $this->trace->info(
            TraceCode::OTPELF_REQUEST,
            [
                'url' => $url,
                'method'  => $method,
                'content' => $content,
                'headers' => $headers,
                'options' => $options
            ]);

        // Update bearer token
        $headers['Authorization'] .= $this->apiKey;

        try
        {
            $response = Requests::request(
                        $url,
                        $headers,
                        json_encode($content),
                        $method,
                        $options);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e);

            return [];
        }

        $response = json_decode($response->body, true);
        $this->trace->info(
            TraceCode::OTPELF_RESPONSE,
            [
                'response' => $response
            ]);

        return $response;
    }
}
