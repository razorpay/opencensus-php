<?php

namespace RZP\Models\Payment\Store;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Exception\ServerErrorException;

class Base
{
    const CODE = "code";
    const ERROR = "error";
    const CONSUMER = "consumer";
    const TYPE = 'type';

    protected $app;

    protected $trace;

    protected $mode;

    protected $config;

    protected $request;

    protected $ba;

    const TASK_ID = 'X-Task-Id';
    const PASSPORT_JWT = 'passport_jwt';
    const X_PASSPORT_JWT_V1 = 'X-Passport-JWT-V1';
    const RESPONSE = 'response';

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->mode = $app['rzp.mode'] ?? 'live';

        $this->request = $app['request'];

        $this->ba = $app['basicauth'];
    }

    const TIMEOUT = 60;

    const CONNECT_TIMEOUT = 10;

    const FETCH_STORES_URL = '/v1/store_hierarchy/stores';

    protected function sendRequest(string $method, string $baseurl, array $data = [])
    {
        $request = [
            'url' => $baseurl.self::FETCH_STORES_URL,
            'method' => $method,
            'content' => $data,
            'headers' => [
                self::TASK_ID => $this->app['request']->getTaskId(),
                self::X_PASSPORT_JWT_V1 => $this->ba->getPassportJwt($baseurl),
            ],
        ];

        if ($method !== Requests::GET) {
            $request['content'] = (empty($data) === false) ? json_encode($data) : [];
        }

        try {
            $this->trace->info(TraceCode::STORE_SERVICE_REQUEST, [
                'request' => $request
            ]);
            $response = $this->sendRawRequest($request);
            $this->trace->info(TraceCode::RESPONSE, [
                'response' => $response
            ]);
        } catch (\Throwable $ex) {
            $this->trace->traceException($ex);
            return [];
        }

        $result = $this->parseResponse($response);
        $responseBody = $result['body'];
        $code = $result['code'];

        if ($code !== 200) {
            $this->trace->error(TraceCode::STORE_SERVICE_ERROR, [
                'response' => $responseBody
            ]);

            return [];
        }

        return $responseBody['stores'];
    }

    protected function sendRawRequest($request)
    {
        try {
            $content = $request['content'];

            if ($request['method'] === 'POST') {
                $content = json_encode($request['content']);
            }

            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $content,
                $request['method']);

        } catch (\Throwable $ex) {
            $this->trace->traceException($ex);

            $this->throwServiceErrorException($ex);
        }

        return $response;
    }

    /**
     * @throws ServerErrorException
     */
    protected function throwServiceErrorException(\Throwable $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_STORE_SERVICE_ERROR;

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    protected function parseResponse($response): array
    {
        $code = null;

        $body = null;

        if ($response !== null) {
            $code = $response->status_code;
            $body = json_decode($response->body, true);
        }

        return [
            'body' => $body,
            'code' => $code,
        ];
    }
}


