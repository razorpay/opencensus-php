<?php

namespace Services;

use Constants\Mode;
use EE\Exception;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

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
            $response = $this->sendRequest('sms/send-otp', 'post', $input);
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
            $response = $this->sendRequest('sms/verify-otp', 'post', $input);
        }

        return $response;
    }

    public function smsCallback($id, $input)
    {
        $relativeUrl = 'sms/'.$id.'/callback';

        $response = $this->sendRequest($relativeUrl, 'post', $input);

        return $response;
    }

    public function sendRequest($url, $method, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
            $data = '';

        $authHeader = 'Basic '. base64_encode($this->key . ':' . $this->secret);

        $headers['Accept'] = 'application/json';
        $headers['Authorization'] = $authHeader;

        $options = array(
            'proxy' => $this->proxy
        );

        $request = array(
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendRavenRequest($request);

        $decodedResponse = json_decode($response->body, true);

        $this->checkErrors($decodedResponse);

        return $decodedResponse;
    }

    protected function sendRavenRequest($request)
    {
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
        $this->trace->info(TraceCode::RAVEN_RESPONSE, $response);

        if (isset($response['error']))
        {
            throw new Exception\BadRequestException($response['error']['internal_error_code']);
        }
    }
}