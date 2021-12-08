<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Requests_Response;
use Requests_Exception;
use Razorpay\Trace\Logger;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;

class Base
{
    protected $app;

    protected $trace;

    protected $mode;

    protected $config;

    protected $key;

    protected $secret;

    protected $baseUrl;

    const VERSION = '/v1';

    const X_REQUEST_ID  = 'X-Request-ID';

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->mode = $app['rzp.mode'] ?? 'live';

        $this->config = $app['config']->get('applications.payouts_service');

        $this->baseUrl = $this->config['url'];

        $this->key = $this->config[$this->mode]['payout_key'];

        $this->secret = $this->config[$this->mode]['payout_secret'];
    }

    const TIMEOUT = 60;

    const CONNECT_TIMEOUT = 10;

    public function makeRequestAndGetContent(array $input, string $action, string $method, array $headers = []) :array
    {
        $request = $this->getRequest($input, $action, $method, $headers);

        $this->tracePayoutServiceRequest($request);

        $response = $this->sendRequest($request);

        $this->tracePayoutServiceResponse($response);

        $responseArray = json_decode($response->body,true);

        $this->checkResponseForError($responseArray);

        return $responseArray;
    }

    public function sendRequest(array $request)
    {
        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                strtoupper($request['method']),
                $request['options']);

            return $response;
        }
        catch (Requests_Exception $e)
        {
            $errorCode = TraceCode::PAYOUT_SERVICE_REQUEST_FAILED;

            if ($this->checkRequestTimeout($e) === true)
            {
                $errorCode = TraceCode::PAYOUT_SERVICE_REQUEST_TIMEOUT;
            }

            $this->trace->traceException($e, Logger::ERROR, $errorCode);

            throw $e;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::PAYOUT_SERVICE_REQUEST_FAILED
            );

            throw $ex;
        }
    }

    public function getRequest(array $input, string $action, string $method, array $headers = [])
    {
        $request = [
            'url' => $this->getUrl($action),
            'method' => $method,
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                self::X_REQUEST_ID           => $this->app['request']->getId(),
            ],
            'content' => empty($input) ? $input: json_encode($input),
            'options' => [
                'auth'            => $this->getAuthDetails(),
                'timeout'         => self::TIMEOUT,
            ]
        ];

        $request['headers'] = array_merge($request['headers'], $headers);

        return $request;
    }

    public function getUrl(string $uri): string
    {
        $url = $this->baseUrl . self::VERSION . $uri;

        return $url;
    }

    public function getAuthDetails(): array
    {
        return [
            $this->key,
            $this->secret,
        ];

    }

    public function tracePayoutServiceRequest(array $request)
    {
        $traceRequest = $request;

        unset($traceRequest['options']['auth']);

        $this->trace->info(TraceCode::PAYOUT_SERVICE_REQUEST, $traceRequest);
    }

    public function tracePayoutServiceResponse(Requests_Response $response)
    {
        $this->trace->info(TraceCode::PAYOUT_SERVICE_RESPONSE, [
            'response'    => $response->body,
            'status_code' => $response->status_code
        ]);
    }

    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param Requests_Exception $e The caught requests exception
     *
     * @return boolean              true/false
     */
    public function checkRequestTimeout(Requests_Exception $e)
    {
        if ($e->getType() === 'curlerror')
        {
            $curlErrNo = curl_errno($e->getData());

            if ($curlErrNo === 28)
            {
                return true;
            }
        }
        return false;
    }

    public function checkResponseForError($response)
    {
        if (is_null($response) === true)
        {
            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR
            );
        }

        if (isset($response[Payout\Entity::ERROR]) === true)
        {
            $this->trace->error(
                TraceCode::PAYOUT_SERVICE_FAILURE_API_RESPONSE,
                [
                    'response' => $response
                ]
            );

            $error = $response[Payout\Entity::ERROR];

            if (strtoupper($error[Error::PUBLIC_ERROR_CODE]) === ErrorCode::BAD_REQUEST_VALIDATION_FAILURE)
            {
                throw new Exception\BadRequestValidationFailureException(
                    $error[Error::DESCRIPTION],
                    $error[Error::FIELD]);
            }
            else if (strtoupper($error[Error::PUBLIC_ERROR_CODE]) === ErrorCode::BAD_REQUEST_ERROR)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    $error[Error::FIELD], null,
                    $error[Error::DESCRIPTION]);
            }
            else
            {
                throw new Exception\ServerErrorException(
                    $error[Error::DESCRIPTION],
                    ErrorCode::SERVER_ERROR,
                    $response
                );
            }
        }
    }
}
