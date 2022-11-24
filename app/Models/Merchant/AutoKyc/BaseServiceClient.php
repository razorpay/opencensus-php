<?php

namespace RZP\Models\Merchant\AutoKyc;

use RZP\Http\Request\Requests;
use \WpOrg\Requests\Response;
use \WpOrg\Requests\Exception as Requests_Exception;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Models\Merchant\Detail\Constants;

trait BaseServiceClient
{
    /**
     * @var array
     */
    protected $responseMetaData = [];

    /**
     * @param array $request
     *
     * @return mixed
     * @throws Exception\IntegrationException
     */
    protected function createAndSendRequest(array $request): array
    {
        $this->addRequestHeaders($request);

        $this->addRequestOptions($request);

        $response = $this->sendRequest($request);

        return [$response, $this->responseMetaData];
    }

    /**
     * @param array $request
     *
     * @return array
     */
    protected function addRequestOptions(array &$request): array
    {
        if (empty($request['options']) === true)
        {
            $request['options'] = [];
        }

        $defaultOptions = [
            'timeout' => $this->timeout,
            'auth'    => $this->getAuthenticationDetails()
        ];

        $request['options'] = array_merge($request['options'], $defaultOptions);

        return $request;
    }

    /**
     * @return array
     */
    protected function getAuthenticationDetails(): array
    {
        $authentication = [
            $this->getAuthUserName(),
            $this->getAuthPassword(),
        ];

        return $authentication;
    }

    /**
     * @param array $request
     */
    protected function addRequestHeaders(array &$request)
    {
        if (empty($request['headers']) === true)
        {
            $request['headers'] = [];
        }

        $defaultHeaders = [
            RequestHeader::X_TASK_ID    => $this->app['request']->getTaskId(),
        ];

        $request['headers'] = array_merge($request['headers'], $defaultHeaders);
    }

    /**
     * @param array $request
     *
     * @return \WpOrg\Requests\Response
     * @throws Exception\IntegrationException
     */
    protected function sendRequest(array $request)
    {
        try
        {
            //
            // json encode if data is must, else ignore.
            //
            if (in_array($request['method'], [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
            {
                $request['content'] = json_encode($request['content'], JSON_FORCE_OBJECT);
            }

            $response = $this->getResponse($request); #for testing update response

            $this->traceResponse($response);

            $this->trace->count(Metric::EXTERNAL_VERIFIER_API_CALL_SUCCESS_TOTAL);

            return $response;

        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::KYC_SERVICE_INTEGRATION_ERROR,
                $this->getTraceableRequest($request));

            $this->trace->count(Metric::EXTERNAL_VERIFIER_API_CALL_FAILED_TOTAL);

            throw new Exception\IntegrationException('
                Could not receive proper response from KYC service');
        }
    }

    /**
     * Filters request array and returns only traceable data
     *
     * @param array $request
     *
     * @return array
     */
    protected function getTraceableRequest(array $request): array
    {
        return array_only($request, ['url', 'method', 'content']);
    }

    /**
     * @param array $request
     *
     * @return \WpOrg\Requests\Response
     * @throws Requests_Exception
     */
    protected function getResponse(array $request)
    {
        $startAt = millitime();

        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $request['content'] ?? [],
            $request['method'],
            $request['options']);

        $timeDuration = millitime() - $startAt;

        $this->responseMetaData[Constants::RESPONSE_TIME]   = $timeDuration;
        $this->responseMetaData[Constants::API_STATUS_CODE] = $response->status_code;

        return $response;
    }

    protected function traceResponse($response)
    {
        $payload = [
            'status_code' => $response->status_code,
            'body'        => $response->body
        ];

        $this->trace->info(TraceCode::KYC_SERVICE_API_RESPONSE, $payload);
    }

    protected function getAuthUserName()
    {
        return $this->config['username'];
    }

    protected function getAuthPassword()
    {
        return $this->config['password'];
    }
}

