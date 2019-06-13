<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;

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

    const CREATE_GATEWAY_RULE  = [
        'url'       =>  "/rule",
        'method'    =>  "POST",
    ];

    const UPDATE_GATEWAY_RULE  = [
        'url'       =>  "/rule",
        'method'    =>  "PUT",
    ];

    const DELETE_GATEWAY_RULE  = [
        'url'       =>  "/rule/:id",
        'method'    =>  "DELETE",
    ];

    const SEND_PAYMENT_DATA  = [
        'url'       =>  "/route",
        'method'    =>  "POST",
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.smart_routing');

        $this->baseUrl = $this->config['url'];

        $this->request = $app['request'];
    }

    public function sendPaymentData($data)
    {
        $this->sendNonBlockingRequest(self::SEND_PAYMENT_DATA, $data);
    }

    public function createGateway($data)
    {
        return $this->sendRequest(self::CREATE_GATEWAY_RULE, $data);
    }

    public function updateGateway($data)
    {
        return $this->sendRequest(self::UPDATE_GATEWAY_RULE, $data);
    }

    public function deleteGateway($id, $group)
    {
        $params = null;

        if (empty($group) === false)
        {
            $params = ['group' => $group];
        }

        return $this->sendRequest(self::DELETE_GATEWAY_RULE, null, $id, $params);
    }

    protected function sendNonBlockingRequest($action, $data = null, $id = null)
    {
        $url = $this->getUrl($action, $id);

        if ($data === null)
        {
            $data = '';
        }

        $headers['Content-Type'] = 'application/json';

        $headers['Accept'] = 'application/json';

        $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();

        $username = $this->app['config']->get('applications.smart_routing.username');

        $password = $this->app['config']->get('applications.smart_routing.password');

        $this->app->nonBlockingHttp->postRequest($url, $data, $headers, $username, $password);
    }


    protected function sendRequest($action, $data = null, $id = null, $params = null)
    {
        try
        {
            $url = $this->getUrl($action, $id, $params);

            if ($data === null)
            {
                $data = '';
            }

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
                'method'  => $action['method'],
                'headers' => $headers,
                'options' => $options,
                'content' => $data
            ];

            $response = $this->sendSmartRoutingRequest($request);

            $this->checkErrors($response);

            return json_decode($response->body, true);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::SMART_ROUTING_SERVICE_ERROR,
                [
                    'response' => $e->getMessage(),
                    'action'   => $action,
                    'data'     => $data,
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
                if ($method === 'POST' or $method === 'PUT')
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
        $responseBody = json_decode($response->body, true);

        $this->trace->info(
            TraceCode::SMART_ROUTING_RESPONSE,
            [
                'response' => $responseBody
            ]);

        if ($response->status_code >= 400)
        {
            throw new Exception\RuntimeException('Smart routing request failed', $responseBody);
        }
    }

    private function getUrl($action, $id, $params) : string
    {
        $url = $this->baseUrl . str_replace_first(':id', $id, $action['url']);

        if (empty($params) == false)
        {
            $url = $url . '?';

            foreach ($params as $key => $value) {

                $url .= $key . '=' . $value . '&';
            }

            $url = rtrim($url, '&');
        }

        return $url;
    }
}
