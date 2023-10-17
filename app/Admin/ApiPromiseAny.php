<?php

namespace App\Admin;

use Trace;
use App\Http\ApiUrl;
use App\Metrics\Constants;
use App\Trace\TraceCode;
use Razorpay\Api\Errors as RZPErrors;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\GuzzleException;
use App\Exceptions\ApiPromiseResponseException;

class ApiPromiseAny
{
    protected $app;

    protected $trace;

    protected $metrics;

    protected $startTime;

    protected $path;

    protected $method;

    protected $promise;

    protected $fulfiledResponse;

    protected $rejectedResponse;

    protected $apiCircuitBreaker;

    const API_ROUTE_NAME_HEADER         = 'Api-Route-Name';

    const API_ROUTE_PATH_PATTERN_HEADER = 'Api-Path-Pattern';

    function __construct(string $path, string $method)
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->metrics = $app['metrics'];

        $this->path = $path;

        $this->method = $method;
    }

    public function setPromise(PromiseInterface $promise): void
    {
        $this->promise = $promise;
    }

    public function setStartTime(): void
    {
        $this->startTime = self::millitime();
    }

    public function setApiResponse(
      ?\GuzzleHttp\Psr7\Response $fulfilled,
      ?\Throwable                $rejected): void
    {
        $this->fulfiledResponse = $fulfilled;

        $this->rejectedResponse = $rejected;
    }

    public function getFulfilledResponse(): ?\GuzzleHttp\Psr7\Response
    {
        return $this->fulfiledResponse;
    }

    public function getRejectedResponse(): ?\Throwable
    {
        return $this->rejectedResponse;
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPromise(): PromiseInterface
    {
        return $this->promise;
    }

    static function millitime(): int
    {
        return round(microtime(true) * 1000);
    }

    public function setApiCircuitBreaker(): void
    {
        $currentRouteName = \Route::currentRouteName() ?? 'unknown_route';
        $path = $this->getPath();
        $method = $this->getMethod();

        $apiRouteCircuitBreaker = new ApiRouteCircuitBreaker($path, $method, $currentRouteName);

        $this->apiCircuitBreaker = $apiRouteCircuitBreaker;
    }

    public function getApiCircuitBreaker(): ApiRouteCircuitBreaker
    {
        return $this->apiCircuitBreaker;
    }

    private function pushDataToMetric($httpCode, $apiPathName, $time_taken): void
    {
        $apiRequestAny = new ApiRequestAny();

        $currentRouteName = \Route::currentRouteName() ?? 'unknown_route';

        $method = $this->getMethod();

        try
        {
            $dimensions = $apiRequestAny->getApiMetricDimensions($httpCode, $currentRouteName, $apiPathName, $method, $time_taken);

            $this->metrics->count(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM, Constants::EVENT_COUNT_ONE, $dimensions);

            $this->metrics->histogram(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM_DURATION, $time_taken, $dimensions);
        }
        catch (\Throwable $t)
        {
            $this->trace->warning(TraceCode::PUSH_METRICS_FAILED, [
                'message' => $t->getMessage() ?? 'unknown_message',
            ]);
        }
    }

    private function processFulfilledResponse(): array
    {
        $path = $this->getPath();
        $method = $this->getMethod();

        $apiRequestAny = new ApiRequestAny();
        $apiRouteCircuitBreaker = $this->getApiCircuitBreaker();

        $fulfilledResponse = $this->getFulfilledResponse();

        $start_time = $this->getStartTime();
        $end_time = self::millitime();

        $time_taken = $end_time - $start_time;

        if ($apiRequestAny->debugLogsEnable() === true)
        {
            Trace::info(TraceCode::API_RESPONSE_METRIC, [
                'api_response_time' => $time_taken,
                'path'              => $path,
                'method'            => $method,
                'isBankingRequest'  => ApiUrl::isBankingOriginRequest(),
                'isPGRequest'       => ApiUrl::isPrimaryOriginRequest(),
            ]);
        }

        try
        {
            $apiRouteName     = $fulfilledResponse->getHeader(self::API_ROUTE_NAME_HEADER);

            $apiPathPattern   = $fulfilledResponse->getHeader(self::API_ROUTE_PATH_PATTERN_HEADER);

            $apiRouteCircuitBreaker->saveApiRouteDetails($apiRouteName[0], $apiPathPattern[0]);

            $apiRouteCircuitBreaker->success();
        }
        catch(\Exception $e)
        {
            Trace::info(TraceCode::API_CIRCUIT_BREAKER_EXCEPTION, [
                'message'     => $e->getMessage(),
                'line_number' => $e->getLine()
            ]);
        }

        $httpCode = $fulfilledResponse->getStatusCode();

        $apiPathName = $apiRouteCircuitBreaker->getApiPathName();

        $this->pushDataToMetric($httpCode, $apiPathName, $time_taken);

        $response = json_decode($fulfilledResponse->getBody(), true);

        return [null, $response, $httpCode];
    }

    /**
     * @throws ApiPromiseResponseException
     */
    private function handleClientException(): void
    {
        $apiRouteCircuitBreaker = $this->getApiCircuitBreaker();
        $apiPathName = $apiRouteCircuitBreaker->getApiPathName();

        $rejected = $this->getRejectedResponse();

        if (!($rejected instanceof \GuzzleHttp\Exception\ClientException))
        {
            return;
        }

        $apiRequestAny = new ApiRequestAny();

        $startTime = $this->getStartTime();
        $endTime = self::millitime();
        $timeTaken = $endTime - $startTime;

        $json = json_decode($rejected->getResponse()->getBody(), true);
        $httpCode = $rejected->getResponse()->getStatusCode();

        Trace::error(
            TraceCode::API_CLIENT_EXCEPTION,
            [
                'message'           => $rejected->getMessage(),
                'api_status_code'   => $httpCode,
                'path'              => $this->getPath(),
                'method'            => $this->getMethod(),
            ]);

        $this->pushDataToMetric($httpCode, $apiPathName, $timeTaken);

        throw new ApiPromiseResponseException($apiRequestAny->getApiErrorDescription($json), $httpCode);
    }

    /**
     * @throws ApiPromiseResponseException
     */
    private function handleServerException(): void
    {
        $apiRouteCircuitBreaker = $this->getApiCircuitBreaker();
        $apiPathName = $apiRouteCircuitBreaker->getApiPathName();

        $rejected = $this->getRejectedResponse();

        if (!($rejected instanceof \GuzzleHttp\Exception\ServerException))
        {
            return;
        }

        $startTime = $this->getStartTime();
        $endTime = self::millitime();
        $timeTaken = $endTime - $startTime;

        $httpCode = $rejected->hasResponse() ? $rejected->getResponse()->getStatusCode() : null;

        Trace::error(
            TraceCode::API_SERVER_EXCEPTION,
            [
                'message'           => $rejected->getMessage(),
                'api_status_code'   => $httpCode,
                'path'              => $this->getPath(),
                'method'            => $this->getMethod(),
            ]);

        $this->pushDataToMetric($httpCode, $apiPathName, $timeTaken);

        throw new ApiPromiseResponseException($rejected->getMessage(), $httpCode);
    }

    /**
     * @throws ApiPromiseResponseException
     */
    private function handleConnectionException(): void
    {
        $apiRouteCircuitBreaker = $this->getApiCircuitBreaker();
        $apiPathName = $apiRouteCircuitBreaker->getApiPathName();

        $rejected = $this->getRejectedResponse();

        if (!($rejected instanceof ConnectException))
        {
            return;
        }

        $startTime = $this->getStartTime();
        $endTime = self::millitime();
        $timeTaken = $endTime - $startTime;

        $httpCode = $rejected->getCode() ?? null;

        app('trace')->error(
            TraceCode::API_CONNECTION_EXCEPTION,
            [
                'message'   => $rejected->getMessage(),
                'path'      => $this->getPath(),
                'method'    => $this->getMethod(),
            ]);

        $this->pushDataToMetric($httpCode, $apiPathName, $timeTaken);

        throw new ApiPromiseResponseException("Error in connecting to API", $httpCode);
    }

    /**
     * @throws ApiPromiseResponseException
     */
    private function handleGuzzleException(): void
    {
        $apiRouteCircuitBreaker = $this->getApiCircuitBreaker();
        $apiPathName = $apiRouteCircuitBreaker->getApiPathName();

        $rejected = $this->getRejectedResponse();

        if (!($rejected instanceof GuzzleException))
        {
            return;
        }

        $startTime = $this->getStartTime();
        $endTime = self::millitime();
        $timeTaken = $endTime - $startTime;

        $httpCode = $rejected->getCode() ?? null;

        Trace::error(
            TraceCode::API_GUZZLE_EXCEPTION,
            [
                'message'           => $rejected->getMessage(),
                'api_status_code'   => $httpCode,
                'path'              => $this->getPath(),
                'method'            => $this->getMethod(),
            ]);

        $this->pushDataToMetric($httpCode, $apiPathName, $timeTaken);

        throw new ApiPromiseResponseException($rejected->getMessage(), $httpCode);
    }

    /**
     * @throws ApiPromiseResponseException
     */
    private function handleRzpError(): void
    {
        $apiRouteCircuitBreaker = $this->getApiCircuitBreaker();
        $apiPathName = $apiRouteCircuitBreaker->getApiPathName();

        $rejected = $this->getRejectedResponse();

        if (!($rejected instanceof RZPErrors\Error))
        {
            return;
        }

        $startTime = $this->getStartTime();
        $endTime = self::millitime();
        $timeTaken = $endTime - $startTime;

        $httpCode = $rejected->getCode() ?? null;

        Trace::error(
            TraceCode::API_RZP_EXCEPTION,
            [
                'message'           => $rejected->getMessage(),
                'api_status_code'   => $httpCode,
                'path'              => $this->getPath(),
                'method'            => $this->getMethod(),
            ]);

        $this->pushDataToMetric($httpCode, $apiPathName, $timeTaken);

        throw new ApiPromiseResponseException($rejected->getMessage(), $httpCode);
    }

    private function markApiCircuitBreakerAsFailure($httpCode): void
    {
        $apiRouteCircuitBreaker = $this->getApiCircuitBreaker();
        $rejected = $this->getRejectedResponse();

        $data = [
            'message' => $rejected->getMessage(),
            'api_status_code' => $httpCode,
        ];

        Trace::error(
            TraceCode::API_REQUEST_FAILURE,
            $data);

        try
        {
            $apiRouteCircuitBreaker->failure($data);
        }
        catch (\Exception $e)
        {
            Trace::info(TraceCode::API_CIRCUIT_BREAKER_EXCEPTION, [
                'message'       => $e->getMessage(),
                'line_number'   => $e->getLine()
            ]);
        }
    }

    private function processRejectedResponse(): array
    {
        $errors = [];

        $rejected = $this->getRejectedResponse();

        $httpCode = null;

        if (!empty($rejected))
        {
            try
            {
                $this->handleClientException();
                $this->handleServerException();
                $this->handleConnectionException();
                $this->handleGuzzleException();
                $this->handleRzpError();
            }
            catch (ApiPromiseResponseException $t)
            {
                $errors = $t->getErrors();
                $httpCode = $t->getHttpCode();
            }
            catch (\Throwable $t)
            {
                $errors = ["Internal error"];
                $httpCode = 500;

                Trace::info(TraceCode::API_CONCURRENT_PROCESS_RESPONSE_FAILURE, [
                    'message'       => $t->getMessage(),
                    'line_number'   => $t->getLine()
                ]);
            }
        }

        if (($rejected !== null) and
            (!($rejected instanceof \GuzzleHttp\Exception\ClientException)))
        {
            $this->markApiCircuitBreakerAsFailure($httpCode);
        }

        return [$errors, null, $httpCode];
    }

    public function processAsyncPromiseResponse(): array
    {
        $fulfilledResponse = $this->getFulfilledResponse();

        $this->setApiCircuitBreaker();

        if (!empty($fulfilledResponse))
        {
            return $this->processFulfilledResponse();
        }

        return $this->processRejectedResponse();
    }

}
