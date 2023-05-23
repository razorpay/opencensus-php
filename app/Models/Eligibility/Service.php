<?php

namespace RZP\Models\Eligibility;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Requests_Exception;
use RZP\Exception;

class Service extends Base\Service
{
    public const FETCH_ELIGIBILITY_ENDPOINT = "/v1/customers/eligibility";
    public const FETCH_ELIGIBILITY_BY_ID_ENDPOINT = "/v1/customers/eligibility/";

    public const PUBLIC_ELIGIBILITY_ENDPOINT = "/v1/public/customers/eligibility";

    public const X_REQUEST_TASK_ID        = 'X-Razorpay-TaskId';
    public const X_PASSPORT_JWT_V1        = 'X-Passport-JWT-V1';
    public const CONTENT_TYPE_HEADER      = 'Content-Type';
    public const AUTHORIZATION            = 'Authorization';
    public const CONTENT_TYPE             = 'application/json';

    protected $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('applications.affordability');

        $this->mode = $this->app['rzp.mode'] ?? Mode::LIVE;
    }


    /**
     * Fetch Customer Eligibility by Input
     *
     * @param array $input
     *
     * @return array
     */
    public function fetchCustomerEligibility($input)
    {
        $url = $this->getBaseUrl() . self::FETCH_ELIGIBILITY_ENDPOINT;

        try
        {
            $response = Requests::post($url, $this->getRequestHeaders(), json_encode($input));
        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::FETCH_ELIGIBILITY_API_REQUEST_FAILED);

            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);
        }

        return $this->formatResponse($response);
    }

    /**
     * Fetch Customer Eligibility by Eligibility Id
     *
     * @param string $eligibilityId
     *
     * @return array
     */
    public function fetchCustomerEligibilityById($eligibilityId)
    {
        $url = $this->getBaseUrl() . self::FETCH_ELIGIBILITY_BY_ID_ENDPOINT. $eligibilityId;
        try
        {
            $response = Requests::get($url, $this->getRequestHeaders());
        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::FETCH_ELIGIBILITY_BY_ID_API_REQUEST_FAILED);
            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);
        }

        return $this->formatResponse($response);
    }

    /**
     * Fetch Public Customer Eligibility by Input
     *
     * @param array $input
     *
     * @return array
     */
    public function fetchPublicCustomerEligibility(array $input)
    {
        $url = $this->getBaseUrl() . self::PUBLIC_ELIGIBILITY_ENDPOINT;

        try
        {
            $response = Requests::post($url, $this->getRequestHeaders(), json_encode($input));
        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::FETCH_PUBLIC_ELIGIBILITY_API_REQUEST_FAILED);

            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);
        }

        return $this->formatResponse($response);
    }

    protected function getBaseUrl(): string
    {
        return $this->config['eligibility_url'][$this->mode];
    }

    protected function getInternalAuthToken(): string
    {
        $username = 'rzp_' . $this->mode;
        $password = $this->config['service_secret'];
        return base64_encode("{$username}:{$password}");
    }

    protected function getRequestHeaders(): array
    {
        $jwt = $this->auth->getPassportJwt($this->getBaseUrl());

        return [
             self::CONTENT_TYPE_HEADER  => self::CONTENT_TYPE,
             self::AUTHORIZATION => 'Basic '. $this->getInternalAuthToken(),
             self::X_PASSPORT_JWT_V1 => $jwt,
             self::X_REQUEST_TASK_ID => $this->app['request']->getTaskId(),
        ];
    }

    /**
     * @param $response
     * @return mixed
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    protected function formatResponse($response)
    {
        if ($response->status_code >= 500) {

            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);

        } else if ($response->status_code >= 400) {

            $error = json_decode($response->body);
            $errorDescription = $error->error->description;

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null, $errorDescription);
        }

        if ($response->body === "null" or $response->body === '') {
            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);
        }

        return json_decode($response->body);
    }
}

