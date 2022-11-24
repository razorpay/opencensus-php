<?php

namespace RZP\Services;


use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Models\Gateway\Downtime;
class SmartRouting
{
    const X_RAZORPAY_TASKID         = 'X-Razorpay-TaskId';
    const X_RAZORPAY_MODE           = 'X-Razorpay-Mode';

    const REQUEST_TIMEOUT           = 1.5;
    const REQUEST_DOWNTIME_TIMEOUT  = 0.5;
    const REQUEST_TIMEOUT_ASYNC     = 0.1;

    const REQUEST_TIMEOUT_AUTHN     = 0.5;

    const MAX_RETRY_COUNT           = 1;

    const SUCCESS                   = 'success';

    const ERROR                     = 'error';

    protected $config;

    protected $baseUrl;

    protected $trace;

    protected $request;

    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    protected $mode;

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

    const SYNC_BUY_PRICING  = [
        'url'       =>  "/sync_terminal_pricing",
        'method'    =>  "POST",
    ];

    const SEND_PAYMNENT_AUTHN = [
        'url'       =>  "/route_authn/api",
        'method'    =>  "POST",
    ];

    const  CREATE_GATEWAY_DOWNTIME_DATA = [
        'url'       => "/create_update_downtime",
        'method'    => "POST"
    ];

    const DELETE_GATEWAY_DOWNTIME_DATA = [
        'url'       => "/resolve_downtime",
        'method'    => "POST"
    ];

    const REFRESH_CACHE_DOWNTIME_DATA = [
        'url'       => "/refresh_cache",
        'method'    => "POST"
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.smart_routing');

        $this->baseUrl = $this->config['url'];

        $this->request = $app['request'];

        $this->mode = $this->app['rzp.mode'];
    }

    public function sendPaymentData($data)
    {
        return $this->sendRequest(self::SEND_PAYMENT_DATA, $data, null, null,self::REQUEST_TIMEOUT);
    }

    public function syncBuyPricingRules($data)
    {
        return $this->sendRequest(self::SYNC_BUY_PRICING, $data, null, null,self::REQUEST_TIMEOUT);
    }

    public function createOrUpdateGatewayDowntimeData($data)
    {
        return $this->sendRequest(self::CREATE_GATEWAY_DOWNTIME_DATA,$data,null,null,self::REQUEST_DOWNTIME_TIMEOUT);
    }

    public function deleteGatewayDowntimeData($data)
    {
        return $this->sendRequest(self::DELETE_GATEWAY_DOWNTIME_DATA,$data,null,null,self::REQUEST_DOWNTIME_TIMEOUT);
    }
    public function sendAuthNPaymentData($data)
    {
        return $this->sendRequest(self::SEND_PAYMNENT_AUTHN, $data, null, null,self::REQUEST_TIMEOUT_AUTHN);
    }

    public function createGatewayRule($data)
    {
        return $this->sendRequest(self::CREATE_GATEWAY_RULE, $data, null, null,self::REQUEST_TIMEOUT);
    }

    public function updateGatewayRule($data)
    {
        return $this->sendRequest(self::UPDATE_GATEWAY_RULE, $data, null, null,self::REQUEST_TIMEOUT);
    }

    public function deleteGatewayRule($id, $group, $step)
    {
        $params = null;

        if (empty($group) === false)
        {
            $params = [
                'group' => $group,
            ];
        }

        if (empty($step) === false)
        {
            $params = [
                'step'  => $step,
            ];
        }

        return $this->sendRequest(self::DELETE_GATEWAY_RULE, null, $id, $params, self::REQUEST_TIMEOUT);
    }

    protected function sendNonBlockingRequest($action, $data = null, $id = null, $params)
    {
        $url = $this->getUrl($action, $id, $params);

        if ($data === null)
        {
            $data = '';
        }

        $headers['Content-Type'] = 'application/json';

        $headers['Accept'] = 'application/json';

        $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();

        $headers[self::X_RAZORPAY_MODE] = $this->mode;

        $username = $this->app['config']->get('applications.smart_routing.username');

        $password = $this->app['config']->get('applications.smart_routing.password');

        $this->app->nonBlockingHttp->postRequest($url, $data, $headers, $username, $password);
    }


    public function sendRequest($action, $data = null, $id = null, $params = null, $timeout = null)
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

            $headers[self::X_RAZORPAY_MODE] = $this->mode;

            $authentication = [
                $this->app['config']->get('applications.smart_routing.username'),
                $this->app['config']->get('applications.smart_routing.password')
            ];

            $options = [
                'timeout' => $timeout,
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
            $traceData = $data;

            if ((array_key_exists('scheduled',$traceData) === false) and (array_key_exists('terminals',$traceData) === true))
            {
                $terminalIds = array_pluck($traceData['terminals'],'id');

                unset($traceData['terminals']);

                unset($traceData['gateway_config']);

                $traceData['terminals'] = $terminalIds;
            }
           if((array_key_exists('scheduled',$traceData) === false) and isset($traceData['scheduled']) === false ) {
               // remove sensitive data from logging
               unset($traceData['payment']['email'], $traceData['payment']['contact'], $traceData['payment']['notes']);
           }
            // checking card key exist or not in array
            if (isset($traceData['payment']['card']) === true)
            {
                unset($traceData['payment']['card']);
            }
            $traceCode = TraceCode::SMART_ROUTING_SERVICE_ERROR;

            if ($action===self::CREATE_GATEWAY_DOWNTIME_DATA || $action===self::DELETE_GATEWAY_DOWNTIME_DATA){

                $traceCode = TraceCode::SMART_ROUTING_DOWNTIME_CACHE_WRITE_ERROR;
            }
            $this->trace->error(
                $traceCode,
                [
                    'response' => $response ?? null,
                    'error'    => $e->getMessage(),
                    'action'   => $action,
                    'data'     => $traceData,
                ]);
            return null;
        }
    }

    protected function sendSmartRoutingRequest($request)
    {
        $method = $request['method'];

        $data = $request['content'];

        $payment_id = "";

        if (isset($data['payment']) === true)
        {
            $payment = $data['payment'];
            $payment_id = $payment['id'];
        }

        $retryCount = 0;

        while (true)
        {
            $this->trace->info(
                TraceCode::SMART_ROUTING_RETRY,
                [
                    'retry_count' => $retryCount,
                    'payment_id'  => $payment_id
                ]);

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
            catch(\WpOrg\Requests\Exception $e)
            {
                // check curl error, increase retry count if timeout
                // throw the error if retry count reaches max allowed value
                $maxRetryCount=self::MAX_RETRY_COUNT;
                if (isset($data['scheduled'])==true){
                    $maxRetryCount = 3;
                }
                if (($retryCount < $maxRetryCount) and
                    (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
                {
                    $this->trace->traceException($e,
                        Trace::ERROR,
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
                    $this->trace->error(
                        TraceCode::SMART_ROUTING_RETRY,
                        [
                            'data'    => $e->getData()
                        ]);
                    throw $e;
                }
            }
        }

        return $response;
    }

    protected function checkErrors($response)
    {

        $responseBody = json_decode($response->body, true);

        $traceResponse = $responseBody;

        // checking whether its terminals selection related response or rule crud related response
        if (( $traceResponse!= null ) and (array_key_exists('success', $traceResponse) === false) and (array_key_exists('scheduled',$traceResponse)==false))
        {
            $newTraceResponse = [];

            foreach ($traceResponse as $terminal)
            {
                unset($terminal['mc_mpan'], $terminal['visa_mpan'], $terminal['rupay_mpan'], $terminal['network_mpan']);

                array_push($newTraceResponse, $terminal);
            }

            $this->trace->info(
                TraceCode::SMART_ROUTING_RESPONSE,
                [
                    'response' => $newTraceResponse
                ]);
        }
        else
        {
            $this->trace->info(
                TraceCode::SMART_ROUTING_RESPONSE,
                [
                    'response' => $traceResponse
                ]);
        }

        if ($response->status_code >= 400)
        {
            throw new Exception\RuntimeException('Smart routing request failed', $responseBody);
        }
    }

    private function getUrl($action, $id, $params = null) : string
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
    /**
     * @param $entity
     */
    public function sendDowntimesSmartRouting($entity): void
    {
        if ($entity['gateway'] != "ALL") {
            $function = "saveOrFail";
            if (!($entity['scheduled']) and ((isset($entity['end']) === true) and ($entity['end']) > 0)) {

                $this->deleteGatewayDowntimes($entity,$function);

            } else {

                $this->createOrUpdateDowntimes($entity,$function);
            }
        }
    }

    /**
     * @param $entity
     */
    public function createOrUpdateDowntimes($entity,$function): void
    {
        $this->createOrUpdateGatewayDowntimeData($entity);

        $this->trace->info(TraceCode::SENDING_DOWNTIME_DATA_TO_SMART_ROUTING, [
            "gateway_downtime_data" => $entity,
            "function" => $function,
            "request" => "createOrUpdate"
        ]);
    }

    /**
     * @param $entity
     */
    public function deleteGatewayDowntimes($entity,$function): void
    {
        $this->deleteGatewayDowntimeData($entity);

        $this->trace->info(TraceCode::SENDING_DOWNTIME_DATA_TO_SMART_ROUTING, [
            "gateway_downtime_data" => $entity,
            "function" => $function,
            "request" => "delete"
        ]);
    }

    /**
     * @param $entity
     */
    public function deleteDowntimesSmartRouting($entity): void
    {
        if ($entity['gateway'] != "ALL") {
            $function = "deleteOrFail";
            $this->deleteGatewayDowntimes($entity,$function);
        }
    }

    public function refreshSmartRoutingCache(){


        $gatewayDowntimes = (new Downtime\Repository)->fetchCurrentAndFutureDowntimes(true,true);
        $data = [
            'gateway_downtime'          => $gatewayDowntimes,
            ];
        return $this->sendRequest(self::REFRESH_CACHE_DOWNTIME_DATA,$data,null,null,self::REQUEST_DOWNTIME_TIMEOUT);
    }
}
