<?php

namespace RZP\Services;

use Requests;
use RZP\Trace\TraceCode;

class RazorXClient
{
    const REQUEST_TIMEOUT = 1; // In seconds

    const EVALUATE_URI    = 'evaluate';

    // Params required for evaluate API
    const ID              = 'id';
    const FEATURE_FLAG    = 'feature_flag';
    const ENVIRONMENT     = 'environment';
    const MODE            = 'mode';

    /**
     * The default case to be returned so that the old flow is taken
     * when the featureFlag is not to be applied to merchant or the
     * response from RazorX server is not return for some reason
     */
    const DEFAULT_CASE    = 'control';

    protected $baseUrl;

    /**
     * API talks to RazorX's APIs using HTTP Basic authentication.
     * Following are those user name and pass.
     */
    protected $key;

    protected $secret;

    protected $config;

    protected $trace;

    protected $env;

    protected $mode;

    public function __construct($app)
    {
        $this->trace   = $app['trace'];
        $this->config  = $app['config']->get('applications.razorx');
        $this->baseUrl = $this->config['url'];
        $this->key     = $this->config['username'];
        $this->secret  = $this->config['secret'];
        $this->env     = $app['env'];
    }

    public function getTreatment(string $id, string $featureFlag, string $mode, array $input = null): string
    {
        $data = [
            self::ID           => $id,
            self::FEATURE_FLAG => $featureFlag,
            self::ENVIRONMENT  => $this->env,
            self::MODE         => $mode
        ];

        if ($input !== null)
        {
            $data = array_merge($data, $input);
        }

        return $this->sendRequest(self::EVALUATE_URI, Requests::GET, $data);
    }

    protected function returnMockVariant(): string
    {
        // TODO: Use headers approach to evaluate the result, default to control
        return self::DEFAULT_CASE;
    }

    protected function sendRequest(
        string $url,
        string $method,
        array $data = [])
    {
        if ($this->config['mock'] === true)
        {
            return $this->returnMockVariant();
        }

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

            return $this->parseAndReturnResponse($response);
        }
        catch(\Throwable $e)
        {
            $this->trace->error(TraceCode::RAZORX_REQUEST_FAILED, ['error' => 'Server error occurred']);

            return self::DEFAULT_CASE;
        }
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        if ($code === 200)
        {
            $response = json_decode($res->body, true);

            return $response['value'] ?? self::DEFAULT_CASE;
        }
        else
        {
            $this->trace->error(TraceCode::RAZORX_REQUEST_FAILED, json_decode($res->body, true));
        }

        return self::DEFAULT_CASE;
    }

    protected function getRequestParams(
        string $url,
        string $method,
        array $data = []): array
    {
        $url = $this->baseUrl . $url;

        if (empty($data) === true)
        {
            $data = '';
        }

        $headers = [];

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

    // TODO: Remove this trace once stable
    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::RAZORX_REQUEST, $request);
    }
}
