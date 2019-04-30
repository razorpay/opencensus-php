<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Card\Validator;


class SmartRouting
{

    const X_RAZORPAY_TASKID  = 'X-Razorpay-TaskId';

    const REQUEST_TIMEOUT = 20;

    const MAX_RETRY_COUNT = 1;

    protected $config;

    protected $baseUrl;

    protected $trace;

    protected $request;

    protected $app;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.routing');

        $this->baseUrl = $this->config['url'];

        $this->request = $app['request'];
    }

    public function sendNonBlockingRequest($url, $method, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
            $data = '';

        $headers['Content-Type'] = 'application/json';

        $headers['Accept'] = 'application/json';

        $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();

        $this->app->nonBlockingHttp->postRequest($url, $data, $headers);
    }

    public function sendRequest($url, $method, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
            $data = '';

        $headers['Content-Type'] = 'application/json';

        $headers['Accept'] = 'application/json';

        $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
        ];

        $request = [
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        ];

        $response = $this->sendSmartRoutingRequest($request);

        $this->checkErrors(json_decode($response->body, true));

        return json_decode($response->body, true);
    }

    protected function sendSmartRoutingRequest($request)
    {
        $method = $request['method'];

        $retryCount = 0;

        while (true)
        {
            try
            {
                $response = Requests::$method(
                    $request['url'],
                    $request['headers'],
                    json_encode($request['content']),
                    $request['options']);

                break;
            }
            catch(\Requests_Exception $e)
            {
                // check curl error, increase retry count if timeout
                // throw the error if retry count reaches max allowed value
                if (($retryCount < self::MAX_RETRY_COUNT) and
                    (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
                {
                    $this->trace->info(
                        TraceCode::CARD_VAULT_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'type'    => $e->getType(),
                            'data'    => $e->getData()
                        ]);

                    $retryCount++;
                }
                else
                {
                    throw $e;
                }
            }
        }

        return $response;
    }

    protected function checkErrors($response)
    {
        $success = $response[self::SUCCESS];

        $this->trace->info(
            TraceCode::SMART_ROUTING_RESPONSE,
            [
                'response' => $response
            ]);

        if ($success === false)
        {
            $error = $response[self::ERROR];

            $data = [
                'error' => $error,
            ];

            throw new Exception\RuntimeException('card vault request failed', $data);
        }
    }
}
