<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Scrooge
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $mode;

    protected $key;

    protected $secret;

    protected $proxy;

    const URLS = [
        'initiate'      => 'refund',
    ];

    const REQUEST_TIMEOUT = 60;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.scrooge');

        $this->baseUrl = $this->config['url'];

        // Refer: https://github.com/razorpay/api/issues/6385
        $this->mode = $app['rzp.mode'];

        $this->key = $this->config['scrooge_key'];

        $this->secret = $this->config['scrooge_secret'];
    }

    public function initiateRefund($input)
    {
        $response = $this->sendRequest(self::URLS['initiate'], 'POST', json_encode($input));

        $code = $response->status_code;

        if (in_array($code, [200, 201, 204], true) === true)
        {
            return [];
        }

        throw new Exception\RuntimeException(
            'Unexpected response code received from Scrooge service.',
            [
                'status_code'   => $code,
                'response_body' => $response->body,
            ]);
    }

    public function sendRequest($url, $method, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
        {
            $data = '';
        }

        $headers['Accept'] = 'application/json';
        $headers['X-Mode'] = $this->mode;

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [
                $this->key,
                $this->secret
            ],
        ];

        $request = [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $headers,
            'options'   => $options,
            'content'   => $data
        ];

        $response = $this->sendScroogeRequest($request);

        $this->trace->info(TraceCode::SCROOGE_RESPONSE, [
            'response' => $response->body
        ]);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::SCROOGE_RESPONSE, $decodedResponse ?? []);

        return $response;
    }

    /**
     * @param $request
     * @return \Requests_Response
     * @throws \Requests_Exception
     */
    protected function sendScroogeRequest($request)
    {
        $this->traceRequest($request);

        try
        {
            $response = Requests::request(
                            $request['url'],
                            $request['headers'],
                            $request['content'],
                            $request['method'],
                            $request['options']);
        }
        // TODO: Check why are we catching this and rethrowing
        catch(\Requests_Exception $e)
        {
            $this->trace->traceException(
                                $e,
                                Trace::ERROR,
                                TraceCode::REFUND_SCROOGE_FAILURE_EXCEPTION,
                                [
                                    'data' => $e->getMessage()
                                ]);

            throw $e;
        }

        return $response;
    }

    protected function traceRequest($request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::SCROOGE_REQUEST, $request);
    }
}
