<?php

namespace RZP\Services;

use RZP\Constants\Mode;
use RZP\Exception;
use Requests;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class Raven
{
    const SMS_ID = 'sms_id';

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $config;

    protected $trace;

    protected $proxy;

    protected $mode;

    const RAVEN_URLS = [
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

        $this->mode = $app['rzp.mode'];

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

        $authHeader = 'Basic '. base64_encode($this->key . ':' . $this->secret);

        $headers['Accept'] = 'application/json';
        $headers['Authorization'] = $authHeader;

        $options = array(
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
                    'response' => $response
                ]);

        $decodedResponse = json_decode($response->body, true);

        $this->checkErrors($decodedResponse);

        return $decodedResponse;
    }

    protected function sendRavenRequest($request)
    {
        $this->trace->info(TraceCode::RAVEN_REQUEST, $request);

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