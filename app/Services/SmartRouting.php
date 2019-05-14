<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Card\Validator;


class SmartRouting
{

    const X_RAZORPAY_TASKID  = 'X-Razorpay-TaskId';

    const REQUEST_TIMEOUT    = 20;

    const MAX_RETRY_COUNT    = 1;

    const SUCCESS            = 'success';

    const ERROR              = 'error';

    protected $config;

    protected $baseUrl;

    protected $trace;

    protected $request;

    protected $app;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.smart_routing');

        $this->baseUrl = $this->config['url'];

        $this->request = $app['request'];
    }

    public function sendNonBlockingRequest($url, $data = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
            $data = '';

        $headers['Content-Type'] = 'application/json';

        $headers['Accept'] = 'application/json';

        $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();

        $username = $this->app['config']->get('applications.smart_routing.username');

        $password = $this->app['config']->get('applications.smart_routing.password');

        $this->app->nonBlockingHttp->postRequest($url, $data, $headers, $username, $password);
    }

    public function sendRequest($url, $method, $data = null)
    {
        try
        {
            $url = $this->baseUrl . $url;

            if ($data === null)
                $data = '';

            $headers['Content-Type'] = 'application/json';

            $headers['Accept'] = 'application/json';

            $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();

            $authentication = [
                $this->app['config']->get('applications.smart_routing.username'),
                $this->app['config']->get('applications.smart_routing.password')
            ];

            $options = [
                'timeout' => self::REQUEST_TIMEOUT,
                'auth'    => $authentication

            ];

            $request = [
                'url'     => $url,
                'method'  => $method,
                'headers' => $headers,
                'options' => $options,
                'content' => $data
            ];

            $response = $this->sendSmartRoutingRequest($request);

            $this->checkErrors(json_decode($response->body, true));

            return json_decode($response->body, true);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::SMART_ROUTING_SERVICE_ERROR,
                [
                    'response' => $e->getMessage()
                ]);
            return null;
        }
    }

    protected function sendSmartRoutingRequest($request)
    {
        $method = $request['method'];

        $retryCount = 0;

        while (true)
        {
            try
            {
                if ($method === 'post' or $method === 'put')
                {
                    $response = Requests::$method(
                        $request['url'],
                        $request['headers'],
                        json_encode($request['content']),
                        $request['options']);
                }
                else
                {
                    $response = Requests::$method(
                        $request['url'],
                        $request['headers'],
                        $request['options']);
                }

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
                        TraceCode::SMART_ROUTING_RETRY,
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

        if ($success === false)
        {
            $error = $response[self::ERROR];

            $data = [
                'error' => $error,
            ];

            throw new Exception\RuntimeException('smart routing request failed', $data);
        }
    }
}
