<?php

namespace RZP\Services;

use Requests;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Order\Metric;
use RZP\Trace\TraceCode;
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

    const PGRouterInitiatePayment = 'v1/payments/initiate';

    const PGRouterPaymentCapture = '/v1/payments/%s/capture';

    const PGRouterPaymentVerify = '/v1/payments/%s/verify';

    // Headers
    const ACCEPT            = 'Accept';
    const X_MODE            = 'X-Mode';
    const CONTENT_TYPE      = 'Content-Type';
    const X_REQUEST_ID      = 'X-Request-ID';

    const REQUEST_TIMEOUT   = 60;

    const RESPONSE_CODE     = 'code';

    const MODE = 'mode';

    /**
     * PGRouter constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
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
        return $this->sendRequest(self::PGRouterValidateAndCreatePayment, Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function fetchPayment(string $id): array
    {
        $url = self::PGRouterFetchPayment . $id;
        $output = $this->sendRequest($url, Requests::GET);

        if ($output['code'] >= 500) // Implement retry, at least twice
        {
            throw new Exception\ServerErrorException('Error with PG Router service',
                ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE);
        }
        if ($output['code'] == 400)
        {
            throw new Exception\BadRequestValidationFailureException(
                $output['body']['error']['internal_error_code']);
        }

        return $output['body']['data'];
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

        if ($output['code'] >= 500) // Implement retry, at least twice
        {
            throw new Exception\ServerErrorException('Error with PG Router service',
                ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE);
        }
        if ($output['code'] == 400)
        {
            throw new Exception\BadRequestValidationFailureException(
                $output['body']['error']['internal_error_code']);
        }

        return $output['body']['data'];
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function paymentVerify(string $id): array
    {
        $url = sprintf(self::PGRouterPaymentVerify, $id);
        $output = $this->sendRequest($url, Requests::GET);

        if ($output['code'] >= 500) // Implement retry, at least twice
        {
            throw new Exception\ServerErrorException('Error with PG Router service',
                ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE);
        }
        if ($output['code'] == 400)
        {
            throw new Exception\BadRequestValidationFailureException(
                $output['body']['error']['internal_error_code']);
        }

        return $output['body']['data'];
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
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     * @param bool   $throwExceptionOnFailure
     *
     * @return array
     */
    protected function sendRequest(
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

        $this->trace->info(TraceCode::PG_ROUTER_RESPONSE, $decodedResponse ?? []);

        return $this->parseResponse($response, $throwExceptionOnFailure);
    }

    /**
     * Function used to set headers for the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]        = 'application/json';
        $headers[self::CONTENT_TYPE]  = 'application/json';
        $headers[self::X_MODE]        = $this->mode;
        $headers[self::X_REQUEST_ID]  = $this->request->getId();

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

        if (isset($traceRequest['content']['order_sync_request']) &&
            isset($traceRequest['content']['order_sync_request']['account_number']))
        {
            unset($traceRequest['content']['order_sync_request']['account_number']);
        }

        $this->trace->info(TraceCode::PG_ROUTER_REQUEST, $traceRequest);
    }

    /**
     * @param \Requests_Response $response
     * @param bool               $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseResponse(\Requests_Response $response, bool $throwExceptionOnFailure = false): array
    {
        $code = $response->status_code;

        if (($throwExceptionOnFailure === true) and
            (in_array($code, [200, 201, 204, 302, 409], true) === false))
        {

            throw new Exception\RuntimeException(
                'Unexpected response code received from PG Router service.',
                [
                    'status_code'   => $code,
                    'response_body' => json_decode($response->body),
                ]);
        }

        return [
            'body' => json_decode($response->body, true),
            'code' => $code,
        ];
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

        // json encode if data is must, else ignore.
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

        $headers = $this->headers;
        $headers['PHP_AUTH_USER'] = $this->auth->getPublicKey();

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
