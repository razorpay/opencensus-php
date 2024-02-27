<?php

namespace RZP\Services;

use Request;
use RZP\Models\Card\IIN\Service as IINService;
use RZP\Http\RequestHeader;
use RZP\Constants\Mode;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Error;

class BinService
{
    const X_RAZORPAY_TASKID = 'grpc-metadata-X-Task-Id';

    const X_NAMESPACE = 'grpc-metadata-x-namespace';

    const SUCCESS           = 'success';

    const MAX_RETRY_COUNT = 1;

    const ERROR             = 'error';

    const UPDATE_IIN        = 'update_iin';

    const FETCH_IIN         = 'fetch_iin';

    protected $baseUrl;

    protected $config;

    protected $trace;

    protected $app;

    protected $request;

    protected $mode;

    private $key;
    /**
     * @var string
     */
    private $secret;

    public function __construct($app = null)
    {
        if (empty($app) === true)
        {
            $app = app();
        }

        $this->mode = $app['rzp.mode'] ?? Mode::LIVE;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.bin_service');

        $this->baseUrl = $this->config['url'];

        $this->request = $app['request'];

        // default for bin service
        $keyName = 'username';
        $secretName = 'password';

        $this->key = $this->config[$keyName];
        $this->secret = $this->config[$secretName];
    }

    protected function getRequestHooks()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        return $hooks;
    }

    public function fetchEntityByIINFromBinService($iin)
    {
        // expand=true helps in fetching flows as well
        $url = 'iins/' . $iin . '?expand=true';

        // To be removed later, once made non-mandatory field in bin services
        $namespace = 'RZP/IN/DEBIT';

        $entity = $this->sendRequest($url, Requests::GET, null, $namespace, BinService::FETCH_IIN);

        if(!empty($entity))
        {
            return (new IINService())->transformBinServiceEntityToApiServiceEntity($entity);
        }

        return [];
    }

    public function sendRequest($url, $method, $data = null, $namespace = null, $action = null)
    {
        try 
        {
            $url = $this->baseUrl . $url;

            if ($data === null) 
            {
                $data = '';
            }

            $headers['Content-Type'] = 'application/json';
            $headers['Accept'] = 'application/json';
            $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();
            $headers[self::X_NAMESPACE] = $namespace;
            $headers['X-Razorpay-Mode'] = $this->app['rzp.mode'] ?? Mode::LIVE;
            $headers['Authorization'] = 'Basic ' . base64_encode($this->key . ':' . $this->secret);
            
            if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
                $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
            }

            $request = [
                'url'       => $url,
                'method'    => $method,
                'headers'   => $headers,
                'content'   => $data
            ];

            $this->trace->info(TraceCode::BIN_SERVICE_REQUEST, [
                'url'       => $request['url'],
                'action'    => $action,
                'content'   => $request['content'],
                'namespace' => $headers[self::X_NAMESPACE]
            ]);

            $response = $this->sendBinServiceRequest($request);

            $this->checkErrors($response);

            return json_decode($response->body, true);
        }
        catch (\Exception $e) 
        {
            $this->trace->error(
                TraceCode::BIN_SERVICE_ERROR,
                [
                    'error_message' => $e->getMessage(),
                ]
            );

            // Log the error and continue with the flow.
            return null;
        }
    }

    protected function sendBinServiceRequest($request)
    {
        $method = $request['method'];

        $retryCount = 0;

        while (true)
        {
            try
            {
                switch($method)
                {
                    case  Requests::GET:
                        $response = Requests::$method(
                                    $request['url'],
                                    $request['headers'],
                                    []);
                        break;
                    case Requests::PATCH:
                        $response = Requests::$method(
                                    $request['url'],
                                    $request['headers'],
                                    json_encode($request['content']));
                        break;
                }

                break;
            }
            catch(\WpOrg\Requests\Exception $e)
            {
                $this->trace->info(
                    TraceCode::BIN_SERVICE_RETRY,
                    [
                        'response'           => $e,
                    ]);

                if (($retryCount < self::MAX_RETRY_COUNT) and
                    (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
                {
                    $this->trace->info(
                        TraceCode::BIN_SERVICE_RETRY,
                        [
                            'message'           => $e->getMessage(),
                            'type'              => $e->getType(),
                            Error\Error::DATA   => $e->getData()
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
            TraceCode::BIN_SERVICE_RESPONSE,
            [
                'response'    => $responseBody,
                'status_code' => $response->status_code,
            ]);
    }
}
