<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class OtpElf
{
    const ERROR_PAGE_UNKNOWN        = 'PAGE_UNKNOWN';
    const ERROR_PARSER_ERROR        = 'PARSER_ERROR';
    const ERROR_PAGE_TYPE_UNKNOWN   = 'PAGE_TYPE_UNKNOWN';
    const ERROR_TYPE_NOT_RECOGNIZED = 'TYPE_NOT_RECOGNIZED';
    const ERROR_INVALID_OTP         = 'INVALID_OTP';
    const ERROR_TIMEOUT             = 'PAYMENT_TIMEOUT';
    const CARD_BLOCKED              = 'CARD_BLOCKED';
    const CARD_INVALID              = 'CARD_INVALID';
    const CARD_NOT_ENROLLED         = 'CARD_NOT_ENROLLED';
    const MOBILE_NOT_UPDATED        = 'MOBILE_NOT_UPDATED';
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

    protected $mode;

    protected $app;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->mode = $this->app['rzp.mode'];

        $this->config = $app['config']->get('applications.otpelf');

        $this->apiKey = $this->config['api_key'];

        $this->baseUrl = $this->config['url'];
    }

    public function otpSend(array $input)
    {
        $response = $this->sendRequest('/', 'POST', $input, 'otpSend');

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

        $response = $this->sendRequest('/act', 'POST', $content, 'otpResend');

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

        $response = $this->sendRequest('/act', 'POST', $content, 'otpSubmit');

        return $response;
    }

    public function sendRequest($url, $method, $content = null, $action = null)
    {
        if ($this->mode === Mode::TEST)
        {
            $mockClass = new Mock\OtpElf($this->app);

            return $mockClass->$action($content);
        }

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

        $traceContent = $content;

        //Removing card number and cvv from trace for firstdata rupay redirect request
        // TODO :: Find a better way to do this
        if (isset($traceContent['request']['content']) === true)
        {
            unset($traceContent['request']['content']['cardnumber']);
            unset($traceContent['request']['content']['cvm']);
        }

        if (isset($traceContent['request']['data']['otp']) === true)
        {
            $traceContent['request']['data']['otp'] = str_repeat('x', strlen($traceContent['request']['data']['otp']));
        }

        $this->trace->info(
            TraceCode::OTPELF_REQUEST,
            [
                'url' => $url,
                'method'  => $method,
                'content' => $traceContent,
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
