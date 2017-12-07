<?php

namespace RZP\Services;

use Requests;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Raven
{
    const SMS_ID = 'sms_id';

    const REQUEST_TIMEOUT = 60;

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $config;

    protected $trace;

    protected $proxy;

    protected $mode;

    const RAVEN_URLS = [
        'send-sms'      => 'sms',
        'send-otp'      => 'sms/send-otp',
        'verify-otp'    => 'sms/verify-otp',
    ];

    protected $validationErrors = [
        ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
    ];

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.raven');

        $this->baseUrl = $this->config['url'];

        // Refer: https://github.com/razorpay/api/issues/6385
        $this->mode = (isset($app['rzp.mode']) === true) ? $app['rzp.mode'] : null;

        $this->key = 'rzp';

        $this->secret = $this->config['secret'];

        $this->proxy = $app['config']->get('gateway.proxy_address');
    }

    public function sendOtp($input)
    {
        $response = null;

        if ($this->mode === Mode::TEST)
        {
            $response['sms_id'] = '10000000000sms';
        }
        else
        {
            $response = $this->sendRequest(self::RAVEN_URLS['send-otp'], 'post', $input);
        }

        return $response;
    }

    public function sendSms($input)
    {
        $response = null;

        if ($this->mode === Mode::TEST)
        {
            $response['sms_id'] = '10000000000sms';
        }
        else
        {
            $response = $this->sendRequest(self::RAVEN_URLS['send-sms'], 'post', $input);
        }

        return $response;
    }

    public function verifyOtp($input)
    {
        $response = null;

        if ($this->mode === Mode::TEST)
        {
            $response['success'] = true;
        }
        else
        {
            $response = $this->sendRequest(self::RAVEN_URLS['verify-otp'], 'post', $input);
        }

        return $response;
    }

    public function smsCallback($id, $input)
    {
        $relativeUrl = 'sms/' . $id . '/callback';

        $response = $this->sendRequest($relativeUrl, 'post', $input);

        return $response;
    }

    public function sendRequest($url, $method, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
        {
            $data = '';
        }

        $headers['Accept'] = 'application/json';

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
            // 'proxy' => $this->proxy
        );

        $request = array(
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendRavenRequest($request);

        $this->trace->info(TraceCode::RAVEN_RESPONSE, [
                    'response' => $response->body
                ]);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::RAVEN_RESPONSE, $decodedResponse ?? []);

        $this->checkErrors($decodedResponse);

        return $decodedResponse;
    }

    protected function sendRavenRequest($request)
    {
        $this->traceRequest($request);

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

    protected function traceRequest($request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::RAVEN_REQUEST, $request);
    }

    protected function checkErrors($response)
    {
        if (isset($response['error']))
        {
            $errorCode = $response['error']['internal_error_code'];

            if (in_array($errorCode, $this->validationErrors, true))
            {
                throw new Exception\BadRequestValidationFailureException(
                    $response['error']['description']);
            }

            throw new Exception\BadRequestException($errorCode);
        }
    }
}
