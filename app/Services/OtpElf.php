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

    public function otpSend($data)
    {
        $response = $this->sendRequest('/', 'POST', $data);

        return $response;
    }

    public function otpResend($data)
    {
        $input = [

        ];

        $response = $this->sendRequest('/act', 'POST', $input);

        return $response;
    }

    public function otpSubmit($data)
    {
        $input = [
            'payment_id' => $data['payment_id'],
            'request'    => [
                'action' => 'submit_otp',
                'data'   => [
                    'otp'    => $data['gateway']['otp']
                ]
            ]
        ];

        $response = $this->sendRequest('/act', 'POST', $input);

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
            'Authorization' => 'Bearer ' . $this->apiKey,
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

        $this->checkErrors(json_decode($response->body, true));

        return json_decode($response->body, true);
    }

    protected function checkErrors($response)
    {
        $this->trace->info(
            TraceCode::OTPELF_RESPONSE,
            [
                'response' => $response
            ]);

        $success = $response['success'];

        if ($success === false)
        {
            throw new Exception\RuntimeException('OtpElf request failed', $data);
        }
    }
}
