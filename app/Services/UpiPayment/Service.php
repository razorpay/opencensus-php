<?php

namespace RZP\Services\UpiPayment;

use App;
use Requests;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Services\UpiPayment\Util;
use Razorpay\Trace\Logger as Trace;

/**
 * Service implements the UPI Payments service client
 */
class Service
{
    /**
     * App container
     *
     * @var mixed
     */
    protected $app;

    /**
     * Used for tracing
     *
     * @var mixed
     */
    protected $trace;

    /**
     * Application Config
     *
     * @var array
     */
    protected $config;

    /**
     * Stores the current Action
     *
     * @var string
     */
    protected $action;

    /**
     * Initiates the app container, trace and UPS config
     */
    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.upi_payment_service');
    }

    /**
     * Action handles all the action based payment requests
     *
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    public function action(string $action, array $input) : array
    {
        $this->action = $action;

        $request = $this->getRequest($action, $input);

        $response = $this->sendRequest($request);

        $serviceResponse = $this->processResponse($response);

        // Other processing to be added here.
        return $serviceResponse;
    }

    /**
     * buildRequestBody builds the request body for UPS
     *
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    protected function buildRequestBody(string $action, array $input): array
    {
        $data = [];

        switch ($action)
        {
            case Action::AUTHORIZE:
                $data = $this->getRequestBodyForAuthorize($action, $input);
                break;

            default:
                throw new Exception\LogicException(
                    'No supported actions found for UPS',
                    null,
                    ['action' => $action]);
        }

        return $data;
    }

    /**
     * getRequest returns the request for UPS
     *
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    protected function getRequest(string $action, array $input): array
    {
        $mode = $this->app['rzp.mode'];

        $domain = $this->config['url'][$mode];

        $content = $this->buildRequestBody($action, $input);

        $request = [
            Request::URL        => $domain . Util::getUri($action),
            Request::METHOD     => Request::POST,
            Request::CONTENT    => $content,
            Request::HEADERS    => $this->getRequestHeaders(),
            Request::OPTIONS    => [
              Request::AUTH_HEADER  => Util::getAuthenticationArray(
                                            $this->config['username'],
                                            $this->config['password']),
            ],
        ];

        return $request;
    }

    /**
     * sendRequest traces the requests, sends the request to UPS
     *
     * @param array $request
     */
    protected function sendRequest(array $request)
    {
        $this->traceRequest($request);

        $request[Request::CONTENT] = Util::arrayToJsonString($request[Request::CONTENT]);

        $response = $this->sendRawRequest($request);

        return $response;
    }

    /**
     * Builds Request Body for Authorize action
     *
     * @param  string $action
     * @param  array  $input
     * @return array
     */
    protected function getRequestBodyForAuthorize(string $action, array $input): array
    {
        Util::convertInputToArray($input);

        $content = [
            Request::PAYMENT    => $input['payment'] ?? null,
            Request::METADATA   => $input['upi'] ?? null,
            Request::TERMINAL   => $input['terminal'] ?? null,
            Request::MERCHANT   => $input['merchant'] ?? null,
            Request::ACTION     => $action,
        ];

        return $content;
    }

    /**
     * Sends a request to UPS
     *
     * @param array $request
     */
    protected function sendRawRequest(array $request)
    {
        $retryCount = 0;

        while (true)
        {
            try
            {
                $response = Requests::request(
                    $request[Request::URL],
                    $request[Request::HEADERS],
                    $request[Request::CONTENT],
                    $request[Request::METHOD],
                    $request[Request::OPTIONS]
                );

                return $response;
            }
            catch(\Requests_Exception $e)
            {
                if ($retryCount < Constant::MAX_RETRY)
                {
                    $this->trace->info(
                        TraceCode::UPI_PAYMENT_SERVICE_REQUEST_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'type'    => $e->getType(),
                            'data'    => $e->getData()
                        ]
                    );

                    $retryCount++;

                    continue;
                }

                $this->throwServerRequestException($e);
            }
        }
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param  \Requests_Exception $e
     * @return void
     */
    protected function throwServerRequestException(\Requests_Exception $e)
    {
        switch (curl_errno($e->getData()))
        {
            case CURLE_OPERATION_TIMEDOUT:
                $errorCode = ErrorCode::SERVER_ERROR_UPI_PAYMENT_SERVICE_REQUEST_TIMEOUT;
                break;

            case CURLE_COULDNT_CONNECT:
                $errorCode = ErrorCode::SERVER_ERROR_UPI_PAYMENT_SERVICE_CONNECTION_FAILED;
                break;

            default:
                $errorCode = ErrorCode::SERVER_ERROR_UPI_PAYMENT_SERVICE_REQUEST_ERROR;
                break;
        }

        $this->trace->traceException(
            $e,
            Trace::WARNING,
            TraceCode::UPI_PAYMENT_SERVICE_REQUEST_ERROR);

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    /**
     * Process the response received from UPS
     *
     * @return array
     */
    protected function processResponse($response): array
    {
        // TODO : trace response, check for errors, process
        // action wise response
        $arrayResponse = Util::jsonToArray($response->body);

        return $arrayResponse;
    }

    protected function traceRequest($request)
    {
        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $traceData = Util::getAuthorizeTraceData($request);
                break;

            default:
                throw new Exception\LogicException(
                    'No supported actions found for UPS',
                    null,
                    ['action' => $this->action]);
        }

        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_REQUEST, $traceData);
    }

    /**
     * Returns all the required header for sending a request to UPS
     *
     * @return array
     */
    protected function getRequestHeaders(): array
    {
        $headers = [
            Request::CONTENT_TYPE_HEADER      => Request::APPLICATION_JSON,
            Request::ACCEPT_HEADER            => Request::APPLICATION_JSON,
            Request::X_RAZORPAY_APP_HEADER    => 'api',
            Request::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
            Request::X_REQUEST_ID             => $this->app['request']->getId(),
            Request::X_RAZORPAY_TRACKID       => $this->app['req.context']->getTrackId(),
        ];

        return $headers;
    }
}
