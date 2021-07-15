<?php

namespace RZP\Services\Pspx;

use App;
use RZP\Exception;
use Monolog\Logger;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use Illuminate\Foundation\Application;

/**
 * PSPx Service is meant to connect to microservice created for writing the business logic for
 * UPI PSP Application. PSPx Service will take care of managing the data required for the psp app.
 */
class Service
{
    /** Header Constants **/
    const CONTENT_TYPE       = 'Content-Type';
    const AUTHORIZATION      = 'Authorization';
    const X_RAZORPAY_TASK_ID = 'X-Razorpay-Task-Id';
    const X_REQUEST_ID       = 'X-Request-Id';

    /**
     * @var $app Application
     */
    protected $app;

    protected $config;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->config = $this->app['config']->get('applications.pspx');
    }


    public function ping()
    {
        $response = $this->sendRequest('GET', Routes::PING_V1);

        $this->trace()->info(TraceCode::PSPX_RESPONSE, [
            'response'  => $response,
        ]);

        return $response;
    }


    /** Request Helpers **/

    /**
     * Sending Raw requests from api
     * Note: This function does not care about versioning
     * The version has top be provided by the consumer of this
     * function
     * @param string $method Method eg. GET , POST, PUT
     * @param string $path Target Endpoint without base, eg. /v1/ping,
     * @param array $payload payload, In case of get it will be converted to query params
     * @param array $headers Override headers in case a certain request requires a custom headers
     * @param array $options Override options in case a certain request requires a custom options
     * @return mixed |null
     * @throws Exception\BadRequestException
     * @throws Exception\RuntimeException
     * @throws Exception\ServerErrorException
     */
    protected function sendRequest(string $method, string $path, $payload = [], $headers = [], $options = [])
    {
        $url = $this->getBaseUrl() . $path;

        $headers = array_merge($this->getHeaders(), $headers);

        $options = array_merge($this->getOptions(), $options);

        $payload = empty($payload) ? [] : json_encode($payload);

        $response = Requests::request($url, $headers, $payload, $method, $options);

        return $this->processResponse($response);
    }

    /**
     * Process Response : Converts the raw response
     * Checks for status code.
     * Performs actions based on error code.
     * TODO: Rudimentary version of processing the response.
     *       We will need to create contracts.
     *       Based on that we can detect actual service failures.
     *       Status Code segregation is very important.
     * @param $response
     * @return mixed
     * @throws Exception\BadRequestException
     * @throws Exception\RuntimeException
     * @throws Exception\ServerErrorException
     */
    protected function processResponse($response)
    {
        // TODO: What kind of status code the service will return ?
        // If 5xx and 4xx both can come, Then How will we recognize a service failure
        $body       = $response->body;
        $statusCode = $response->status_code;

        // Case 1: In case of bad request
        if ($statusCode >= 400 && $statusCode < 500)
        {
            $body = $this->transformJSONResponse($body);

            throw new Exception\BadRequestException(
                $body['code'] ?? ErrorCode::BAD_REQUEST_ERROR,
                $body['field'] ?? null,
                $body,
                $body['description'] ?? null
            );
        }

        // Case 2: In case of Server Error

        // Case 2.1: Service Unavailable
        if ($statusCode === 503)
        {
            throw new Exception\ServerErrorException(
                'PSPx Service is unreachable',
                ErrorCode::SERVER_ERROR_PSPX_SERVICE_UNAVAILABLE
            );
        }

        // Case 2.2 Other 5xx errors
        if ($statusCode >= 500)
        {
            throw new Exception\ServerErrorException(
                'Internal Server Error',
                ErrorCode::SERVER_ERROR_PSPX_SERVICE_ERROR,
                $this->transformJSONResponse($body)
            );
        }

        return $this->transformJSONResponse($body);
    }

    /**
     * Helper function to convert json string response
     * to array , and handle edge cases
     * @param $response
     * @return mixed
     * @throws Exception\RuntimeException
     */
    protected function transformJSONResponse($response)
    {
        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'Failed to convert json to array',
                [
                    'json'  => $response,
                ]);
        }

        return $decodedResponse;
    }

    protected function getHeaders(): array
    {
        return [
            self::CONTENT_TYPE          => 'application/json',
            self::AUTHORIZATION         => $this->getAppBasicAuthHeaders(),
            self::X_RAZORPAY_TASK_ID    => $this->app['request']->getTaskId(),
            self::X_REQUEST_ID          => $this->app['request']->getId(), // Do we need this ????
        ];
    }

    /**
     * Return CURL options , like timeouts, etc.
     * @return array
     * TODO: Check what options should go here
     */
    protected function getOptions(): array
    {
        return [];
    }

    protected function getAppBasicAuthHeaders(): string
    {
        $username = $this->config['username'];
        $password = $this->config['password'];

        return 'Basic '. base64_encode($username.':'.$password);
    }

    protected function getBaseUrl(): string
    {
        return $this->config['url'] ?? '';
    }

    /** Tracing and Metrics **/
    protected function trace(): Logger
    {
        return app('trace');
    }
}
