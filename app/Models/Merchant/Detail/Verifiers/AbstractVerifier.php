<?php

namespace RZP\Models\Merchant\Detail\Verifiers;

use App;
use Requests;
use Requests_Response;
use Requests_Exception;

use RZP\Exception;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Http\RequestHeader;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Models\Merchant\Detail\Constants;

abstract class AbstractVerifier
{
    protected $config;

    protected $trace;

    protected $app;

    protected $input;

    protected $mockStatus = 'success';

    protected $merchant;

    /**
     * @var int Default timeout
     */
    protected $timeout = 10; //seconds

    public function __construct(array $input, Merchant\Entity $merchant)
    {
        $app = App::getFacadeRoot();

        $this->app    = $app;
        $this->config = $app['config']['applications.mozart'];
        $this->trace  = $app['trace'];

        $this->merchant = $merchant;
        $this->input  = $input;
    }

    protected function validateResponse(Requests_Response $response)
    {
        if ($response->status_code !== 200)
        {
            $payload['body'] = $response->body;

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CAPITAL_INTEGRATION, null, $payload);
        }
    }

    /**
     * @param array $request
     *
     * @return mixed
     * @throws Exception\BadRequestException
     * @throws Exception\IntegrationException
     */
    protected function createAndSendRequest(array $request)
    {
        $this->addRequestHeaders($request);

        $this->addRequestOptions($request);

        $response = $this->sendRequest($request);

        return json_decode($response->body, true);
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
            'api',
            $this->config['password']
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
            RequestHeader::X_TASK_ID => $this->app['request']->getTaskId(),
        ];

        $request['headers'] = array_merge($request['headers'], $defaultHeaders);
    }

    /**
     * @param array $request
     *
     * @return Requests_Response
     * @throws Exception\BadRequestException
     * @throws Exception\IntegrationException
     */
    protected function sendRequest(array $request): Requests_Response
    {
        try
        {
            $this->trace->info(TraceCode::CAPITAL_INTEGRATION_API_REQUEST, $this->getTraceableRequest($request));

            // json encode if data is must, else ignore.
            if (in_array($request['method'], [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
            {
                $request['content'] = json_encode($request['content'], JSON_FORCE_OBJECT);
            }

            $response = $this->getResponse($request);

            $this->traceResponse($response);

            $dimensions = $this->getMetricDimensions();

            $this->validateResponse($response);

            $this->trace->count(Metric::EXTERNAL_VERIFIER_API_CALL_SUCCESS_TOTAL, $dimensions);

            return $response;

        }
        catch (Requests_Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CAPITAL_INTEGRATION_ERROR,
                $this->getTraceableRequest($request));

            $dimensions = $this->getMetricDimensions();

            $this->trace->count(Metric::EXTERNAL_VERIFIER_API_CALL_FAILED_TOTAL, $dimensions);

            throw new Exception\IntegrationException('
                Could not receive proper response from capital service');
        }
    }

    protected function traceResponse(Requests_Response $response)
    {
        $payload = [
            'status_code' => $response->status_code,
            'body'        => $response->body
        ];

        $this->trace->info(TraceCode::CAPITAL_INTEGRATION_API_RESPONSE, $payload);
    }

    /**
     * Filters request array and returns only traceable data
     *
     * @param  array  $request
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
     * @return Requests_Response
     * @throws Requests_Exception
     */
    protected function getResponse(array $request)
    {
        $startAt = millitime();

        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['method'],
            $request['options']);

        $timeDuration = millitime() - $startAt;

        $statusCode = $this->getStatusCodeForVerifier($response);

        $this->traceAndPushEvent($timeDuration, $statusCode);

        return $response;
    }

    protected function traceAndPushEvent($timeDuration, $statusCode)
    {
        $dimensions = $this->getMetricDimensions();

        $this->trace->histogram(Metric::EXTERNAL_VERIFIER_API_CALL_DURATION_MS, $timeDuration, $dimensions);

        $eventAttribute = [
            Constants::RESPONSE_TIME => $timeDuration,
            Constants::STATUS_CODE => $statusCode,
            Constants::DOCUMENT_TYPE => $this->input[Constants::DOCUMENT_TYPE] ?? '',
        ];

        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_VERIFIER_SERVICE_RESPONSE_TIME,
                                                 $this->merchant,
                                                 null,
                                                 $eventAttribute);
    }

    public function setMockStatus(string $status)
    {
        $this->mockStatus = $status;
    }

    abstract public function verifyDetails();

    public function getMetricDimensions(): array
    {
        $dimensions = [
            Constants::EXTERNAL_VERIFIER => get_class($this)
        ];

        return $dimensions;
    }

    protected function getStatusCodeForVerifier(Requests_Response $response)
    {
        $body = json_decode($response->body, true);

        $statusCode = $body['data']['content']['response']['status-code'] ??
                      ($body['data']['content']['response']['statusCode'] ??
                       ($body['data']['content']['response']['status'] ??
                        $response->status_code));

        return $statusCode;
    }
}
