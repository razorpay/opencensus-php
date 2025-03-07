<?php

namespace RZP\Services\Device;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Exception\ServerErrorException;

class Base
{
    const CODE = "code";
    const ERROR = "error";

    protected $app;

    protected $trace;

    protected $mode;

    protected $config;

    protected $request;

    protected $username;

    protected $password;

    const TASK_ID  = 'X-Task-Id';
    const AUTHORIZATION  = 'Authorization';
    const RESPONSE = 'response';

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->mode = $app['rzp.mode'] ?? 'live';

        $this->request = $app['request'];

        $this->config = $app['config']->get('applications.ezetap-api');

        $this->username = $this->config['key'];

        $this->password = $this->config['secret'];
    }

    const TIMEOUT = 60;

    const CONNECT_TIMEOUT = 10;

    protected function sendRequest(string $method, string $url, array $data = [])
    {
        $request = [
            'url'     => $url,
            'method'  => $method,
            'content' => $data,
            'headers' => [
                self::TASK_ID             => $this->app['request']->getTaskId(),
                self::AUTHORIZATION     => 'Basic '. base64_encode($this->username . ':' . $this->password)
            ],
        ];

        if ($method !== Requests::GET)
        {
            $request['content'] = (empty($data) === false) ? json_encode($data) : [];
        }

        try {
            $this->trace->info(TraceCode::DEVICE_SERVICE_REQUEST, [
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

        list($responseBody, $code) = $this->parseResponse($response);

        if($code !== 200)
        {
            $this->trace->error(TraceCode::DEVICE_SERVICE_ERROR, [
                'response' => $responseBody
            ]);

            return [];
        }

        return $responseBody;
    }

    protected function sendRawRequest($request)
    {
        try
        {
            $content = $request['content'];

            if ($request['method'] === 'POST')
            {
                $content = json_encode($request['content']);
            }

            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $content,
                $request['method']);

        }
        catch(\Throwable $ex)
        {
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
        $errorCode = ErrorCode::SERVER_ERROR_EZETAP_SERVICE_ERROR;

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    protected function parseResponse($response): array
    {
        $code = null;

        $body = null;

        if($response !== null)
        {
            $code = $response->status_code;
            $body = json_decode($response->body, true);
        }

        return [
            'body' => $body,
            'code' => $code,
        ];
    }
}


