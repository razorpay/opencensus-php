<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

use Razorpay\OAuth\Token;
use Razorpay\OAuth\Application;

class AuthService
{
    const REQUEST_TIMEOUT = 30; // In seconds

    protected $baseUrl;

    //
    // API talks to Authentication Service's APIs using HTTP Basic authentication.
    // Following are those user name and pass.
    //
    protected $key;

    protected $secret;

    protected $config;

    protected $trace;

    public function __construct($app)
    {
        $this->key     = 'rzp';
        $this->trace   = $app['trace'];
        $this->config  = $app['config']->get('applications.auth_service');
        $this->baseUrl = $this->config['url'];
        $this->secret  = $this->config['secret'];
    }

    public function createApplication(array $input, string $merchantId): array
    {
        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('applications', Requests::POST, $input);
    }

    public function getApplication(string $id, string $merchantId): array
    {
        $input = [Application\Entity::MERCHANT_ID => $merchantId];

        return $this->sendRequest('applications/' . $id, Requests::GET, $input);
    }

    public function getMultipleApplications(array $input, string $merchantId): array
    {
        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('applications', Requests::GET, $input);
    }

    public function deleteApplication(string $id, string $merchantId): array
    {
        $input = [Application\Entity::MERCHANT_ID => $merchantId];

        return $this->sendRequest('applications/' . $id, Requests::DELETE, $input);
    }

    public function updateApplication(string $id, array $input, string $merchantId): array
    {
        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('applications/' . $id, Requests::PATCH, $input);
    }

    public function getTokens(array $input, string $merchantId): array
    {
        $input[Token\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('tokens', Requests::GET, $input);
    }

    public function getToken(string $id, array $input, string $merchantId): array
    {
        $input[Token\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('tokens/' . $id, Requests::GET, $input);
    }

    public function revokeToken(string $id, array $input, string $merchantId): array
    {
        $input[Token\Entity::MERCHANT_ID] = $merchantId;

        return $this->sendRequest('tokens/' . $id, Requests::DELETE, $input);
    }

    protected function sendRequest(
        string $url,
        string $method,
        array $data = null)
    {
        $request = $this->getRequestParams($url, $method, $data);

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
        catch(\Throwable $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_AUTH_SERVICE_FAILURE
            );
        }

        return $this->parseAndReturnResponse($response);
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        //
        // If returned status code is 2XX, everything is fine
        // and just return the decoded JSON body.
        //
        if (in_array($code, [200, 201], true))
        {
            return json_decode($res->body, true);
        }

        //
        // If returned status code is 400 we parse the JSON body and
        // raise validation exception in API format.
        //
        elseif ($code === 400)
        {
            $error = json_decode($res->body, true)['error']['description'];

            throw new Exception\BadRequestValidationFailureException($error);
        }

        //
        // Else we return a generic API's bad request error with a message
        // and log response body in trace.
        //
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_AUTH_SERVICE_ERROR,
                null,
                json_decode($res->body, true)
            );
        }
    }

    protected function getRequestParams(
        string $url,
        string $method,
        array $data = null): array
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
        {
            $data = '';
        }

        $headers['Accept'] = 'application/json';

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
        ];

        return [
            'url'     => $url,
            'method'  => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data,
        ];
    }

    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::AUTH_SERVICE_REQUEST, $request);
    }
}
