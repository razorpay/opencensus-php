<?php

namespace RZP\Services;

use App;
use RZP\Http\Request\Requests;
use RZP\Constants\Entity;
use RZP\Error\Error;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Error\ErrorClass;
use RZP\Models\Order\Metric;
use Razorpay\Trace\Logger as Trace;

class PGRouter
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $mode;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $auth;

    const PGRouterOrderSyncUrl = 'v1/sync/order';

    const PGRouterUpdateSyncedOrderUrl = 'v1/update/synced/order';

    const PGRouterBulkOrderSyncUrl = 'v1/bulk/sync/orders';

    // Payment related PG Router APIs below

    // Deliberately using this URL. When PG Router becomes face of orders & payments, integrated merchants will hit this URL
    // and PG Router will take this as first call to create paymentId and perform validations. So, will be easy to switch in future.
    const PGRouterValidateAndCreatePayment = 'v1/payments/create/ajax';

    const PGRouterFetchPayment = 'v1/payments/';

    const PGRouterFetchCard    = 'v1/cards/';

    const PGRouterInitiatePayment = 'v1/payments/initiate';

    const PGRouterPaymentCapture = '/v1/payments/%s/capture';

    const PGRouterPaymentVerify = '/v1/payments/%s/verify';

    const PGRouterPaymentCancel = '/v1/payments/%s/cancel';

    const PG_ROUTER_FAILURE_STATUS_CODE = "pg_router_failure_status_code";

    // Headers
    const ACCEPT            = 'Accept';
    const X_MODE            = 'X-Mode';
    const CONTENT_TYPE      = 'Content-Type';
    const X_REQUEST_ID      = 'X-Request-ID';
    const X_REQUEST_TASK_ID = 'X-Razorpay-TaskId';

    const REQUEST_TIMEOUT   = 60;

    const RESPONSE_CODE     = 'code';

    const MODE = 'mode';

    /**
     * PGRouter constructor.
     *
     * @param $app
     */
    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.pg_router');

        $this->baseUrl = $this->config['url'];

        $this->mode = $app['rzp.mode'];

        $this->request = $app['request'];

        $this->key = $this->config['pg_router_key'];

        $this->secret = $this->config['pg_router_secret'];

        $this->auth = $app['basicauth'];

        $this->setHeaders();
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function validateAndCreatePayment(array $input, bool $throwExceptionOnFailure = false): array
    {
        $output = $this->sendRequest(self::PGRouterValidateAndCreatePayment, Requests::POST, $input, $throwExceptionOnFailure);

        return $output['body'];
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function paymentCapture(string $id, array $captureParams, bool $throwExceptionOnFailure = false): array
    {
        $url = sprintf(self::PGRouterPaymentCapture, $id);

        $output = $this->sendRequest($url, Requests::POST, $captureParams, $throwExceptionOnFailure);

        return $output['body']['data']['payment'];
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function paymentCancel(string $id, string $merchantId, bool $throwExceptionOnFailure = false): array
    {
        $url = sprintf(self::PGRouterPaymentCancel, $id);

        if (empty($merchantId) === false)
        {
            $url .= '?merchant_id='.$merchantId;
        }

        $output = $this->sendRequest($url, Requests::GET, [], $throwExceptionOnFailure);

        return $output['body'];
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function paymentVerify(string $id, bool $throwExceptionOnFailure = false): array
    {
        $url = sprintf(self::PGRouterPaymentVerify, $id);

        $output = $this->sendRequest($url, Requests::GET, [], $throwExceptionOnFailure);

        return $output['body']['data']['payment'];
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function initiatePayment(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::PGRouterInitiatePayment, Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function syncOrderToPgRouter(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::PGRouterOrderSyncUrl, Requests::POST, $input, $throwExceptionOnFailure);
    }

    public function updateSyncedOrderToPgRouter(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::PGRouterUpdateSyncedOrderUrl, Requests::PATCH, $input, $throwExceptionOnFailure);
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function syncBulkOrderToPgRouter(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::PGRouterBulkOrderSyncUrl, Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param string $id
     * @param array $input
     *
     * @return array
     */
    public function fetch(string $entity, string $id, string $merchantId, array $input)
    {
        if ($entity === Entity::CARD)
        {
            $endpoint = 'v1/cards/' . $id;
        }
        else
        {
            $endpoint = 'v1/payments/' . $id;
        }


        $card = null;

        if (empty($merchantId) === false)
        {
            $endpoint .= '?merchant_id='.$merchantId;
        }

        $response = $this->sendRequest($endpoint, Requests::GET, [], false);

        if (empty($response) === false and isset($response['body']['data']['payment']))
        {
            if (isset($response['body']['data']['payment']['acquirer_data']) === true and
                isset($payment['body']['data']['payment']['acquirer_data']['auth_code']) === true)
            {
                $response['body']['data']['payment']['reference2'] =
                    $response['body']['data']['payment']['acquirer_data']['auth_code'];
            }

            if (isset($response['body']['data']['payment']['card']) === true)
            {
                $response['body']['data']['payment']['card']['id'] = $response['body']['data']['payment']['id'];

                $card = (new Card\Entity)->forceFill($response['body']['data']['payment']['card']);

                unset($response['body']['data']['payment']['card']);
            }

            $payment = (new Payment\Entity)->forceFill($response['body']['data']['payment']);

            if ($card !== null)
            {
                $payment->card()->associate($card);
            }

            return $payment;
        }

        if (empty($response) === false and isset($response['body']['data']['card']))
        {
            $card = (new Card\Entity)->forceFill($response['body']['data']['card']);

            return $card;
        }

        return null;
    }

    public function save(string $id, array $input)
    {
        $endpoint = 'v1/payments/'.$id;

        $this->sendRequest($endpoint, Requests::POST, $input, false);

        return null;
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     * @param bool   $throwExceptionOnFailure
     *
     * @return array
     */
    public function sendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        bool $throwExceptionOnFailure = false): array
    {
        $request = $this->generateRequest($endpoint, $method, $data);

        $startTime = microtime(true);

        $response = $this->sendPGRouterRequest($request);

        $this->logResponseTimeOfPgRouter($startTime, $request['url']);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::PG_ROUTER_RESPONSE,
            ["response" => $decodedResponse ?? [],
                "statusCode" =>  $response->status_code
            ]);

        return $this->parseResponse($decodedResponse, $response->status_code, $throwExceptionOnFailure);
    }

    /**
     * Function used to set headers for the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]              = 'application/json';
        $headers[self::CONTENT_TYPE]        = 'application/json';
        $headers[self::X_MODE]              = $this->mode;
        $headers[self::X_REQUEST_ID]        = $this->request->getId();
        $headers[self::X_REQUEST_TASK_ID]   = $this->request->getTaskId();

        $this->headers = $headers;
    }

    /**
     * @param array $request
     *
     * @return \Requests_Response
     * @throws \Requests_Exception
     */
    protected function sendPGRouterRequest(array $request): \Requests_Response
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
        catch(\Requests_Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_ROUTER_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);

            throw $e;
        }

        return $response;
    }

    /**
     * @param array $request
     */
    protected function traceRequest(array $request)
    {
        $traceRequest = $request;

        unset($traceRequest['options']['auth']);

        if (is_array($traceRequest['content']) === true)
        {
            unset($traceRequest['content']['order_sync_request']['account_number']);

            unset($traceRequest['content']['card']['number']);

            unset($traceRequest['content']['card']['cvv']);

        }
        else
        {
            $content = json_decode($traceRequest['content'], true);

            unset($content['card']['number']);

            unset($content['card']['cvv']);

            $traceRequest['content'] = json_encode($content);
        }

        $this->trace->info(TraceCode::PG_ROUTER_REQUEST, $traceRequest);
    }

    /**
     * @param $response
     * @param $statusCode
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\InvalidArgumentException
     * @throws Exception\ServerErrorException
     */
    protected function parseResponse($response, $statusCode, bool $throwExceptionOnFailure = false): array
    {
        if (in_array($statusCode, [503], true) === true)
        {
            //TODO: Check if we have to disable admin config for rearch routing logic
            throw new Exception\ServerErrorException('PG Router Service is unreachable', ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE);
        }

        if ($response === null)
        {
            throw new Exception\ServerErrorException('PG Router Response cannot be null', ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE);
        }

        if ($throwExceptionOnFailure === true)
        {
            $this->checkForErrors($response,$statusCode);
        }

        return [
            'body' => $response,
            'code' => $statusCode,
        ];
    }

    public function checkForErrors($response, $statusCode)
    {
        if (isset($response['error']) === false)
        {
            return;
        }

        $errorCode = $response['error']['code'];

        $description = $response['error']['description'];

        $metadata = null;

        if (isset($response['error']['metadata']) === true)
        {
            $metadata = $response['error']['metadata'];
        }

        $errorData = [];

        //TODO: Get method from pg router and update here
        $errorData['method'] = "card";

        if (($metadata != null) and
            (isset($metadata['payment_id']) === true))
        {
            $errorData['payment_id'] = $metadata['payment_id'];
        }

        if (($metadata != null) and
            (isset($metadata['order_id']) === true))
        {
            $errorData['order_id'] = $metadata['order_id'];
        }

        $internalErrorCode = $response['internal']['code'];

        $dimensions =[
            "status_code" => $statusCode,
            "internal_error_code"=>$internalErrorCode
        ];
        $this->trace->count(self::PG_ROUTER_FAILURE_STATUS_CODE, $dimensions);

        $class = Error::getErrorClassFromErrorCode($errorCode);

        switch ($class)
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors($internalErrorCode, $description,$errorData);
                break;

            case ErrorClass::BAD_REQUEST:
                throw new Exception\BadRequestException(
                    $internalErrorCode, null, $errorData, $description);

            case ErrorClass::SERVER:
                throw new Exception\ServerErrorException('Error with PG Router service',
                    ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE, $errorData);

            default:
                throw new Exception\InvalidArgumentException('Not a valid error code class',
                    array_merge(['errorClass' => $class], $errorData));
        }
    }

    protected function handleGatewayErrors($internalErrorCode, $description, $errorData)
    {
        switch ($internalErrorCode)
        {
            case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                throw new Exception\GatewayRequestException($description,null,false,$errorData);

            case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                throw new Exception\GatewayTimeoutException($description,null,false,$errorData);

            default:
                throw new Exception\GatewayErrorException($internalErrorCode,
                    null,null, $errorData);
        }
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data): array
    {
        $url = $this->baseUrl . $endpoint;

        // json encode if data is must, else ignore
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [
                $this->key,
                $this->secret
            ],
        ];

        $this->setHeaders();

        $headers = $this->headers;

        $headers['PHP_AUTH_USER'] = $this->auth->getPublicKey();

        if (isset($this->app['rzp.mode']) and $this->app['rzp.mode'] === 'test')
        {
            $testCaseId = $this->request->header('X-RZP-TESTCASE-ID');

            if (empty($testCaseId) === false)
            {
                $headers['X-RZP-TESTCASE-ID'] = $testCaseId;
            }
        }

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

    private function logResponseTimeOfPgRouter(float $startTime, string $requestUrl)
    {
        try
        {
            $responseTime = get_diff_in_millisecond($startTime);

            $dimensions = [
                "request_url" => $requestUrl
            ];

            $this->trace->histogram(Metric::PG_ROUTER_ORDER_SYNC_RESPONSE_TIME, $responseTime, $dimensions);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_ROUTER_ERROR_LOGGING_RESPONSE_TIME_METRIC
            );
        }
    }
}
