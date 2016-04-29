<?php

namespace Services;

use EE\Exception;
use Requests;
use Trace\Trace;
use Trace\TraceCode;

class Raven
{
    const SUCCESS       = 'success';

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $config;

    protected $trace;

    protected $proxy;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.raven');

        $this->baseUrl = $this->config['url'];

        $this->key = 'rzp';

        $this->secret = $this->config['secret'];

        $this->proxy = $app['config']->get('gateway.proxy_address');
    }

    public function sendOtp($input)
    {
        $response = $this->sendRequest('sms/send-otp', 'post', $input);

        return $response;
    }

    public function verifyOtp($input)
    {
        $response = $this->sendRequest('sms/verify-otp', 'post', $input);

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

        $headers['Content-Type'] = 'application/json';
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

        $this->checkErrors(json_decode($response->body, true));

        return json_decode($response->body, true);
    }

    protected function sendRavenRequest($request)
    {
        $method = $request['method'];

        try
        {
            $response = Requests::$method(
                $request['url'],
                $request['headers'],
                json_encode($request['content']),
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
        $success = false;

        if (isset($response[self::SUCCESS]))
        {
            $success = $response[self::SUCCESS];
        }

        $this->trace->info(
            TraceCode::RAVEN_REQUEST,
            [
                'response' => $response
            ]);

        if($success === false)
        {
            throw new Exception\RuntimeException('raven request failed');
        }
    }
}