<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;

class OtpElf
{
    const ERROR_PAGE_UNKNOWN        = 'PAGE_UNKNOWN';
    const ERROR_PARSER_ERROR        = 'PARSER_ERROR';
    const ERROR_PAGE_TYPE_UNKNOWN   = 'PAGE_TYPE_UNKNOWN';
    const ERROR_TYPE_NOT_RECOGNIZED = 'TYPE_NOT_RECOGNIZED';
    const ERROR_INVALID_OTP         = 'INVALID_OTP';
    const ERROR_TIMEOUT             = 'PAYMENT_TIMEOUT';
    const CARD_BLOCKED              = 'CARD_BLOCKED';
    const NETWORK_ERROR             = 'NETWORK_ERROR';
    const BANK_ERROR                = 'BANK_ERROR';
    const PAYMENT_TIMEOUT           = 'PAYMENT_TIMEOUT';
    const BANK_SERVICE_DOWN         = 'BANK_SERVICE_DOWN';
    const NO_AVAILABLE_ACTIONS      = 'NO_AVAILABLE_ACTIONS';


    public static $otpElfErrors = [
        self::ERROR_PAGE_UNKNOWN,
        self::ERROR_PARSER_ERROR,
        self::ERROR_PAGE_TYPE_UNKNOWN,
        self::ERROR_TYPE_NOT_RECOGNIZED,
    ];

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
            'payment_id' => $input['payment_id'],
            'request'    => [
                'action' => 'resend_otp',
            ]
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
