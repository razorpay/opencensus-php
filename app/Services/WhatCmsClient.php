<?php

namespace RZP\Services;
use App;
use \WpOrg\Requests\Exception as Requests_Exception;
use \WpOrg\Requests\Response;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;

class WhatCmsClient
{
    protected $baseUrl;

    protected $secret;

    protected $config;

    protected $trace;

    protected $app;

    const JSON_METHOD = [self::POST, self::PUT, self::PATCH];

    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']['applications.whatcms'];

        $this->baseUrl = $this->config['base_url'];

        $this->secret = $this->config['secret'];
    }

    public function getWebsiteInfo($websiteUrl)
    {
        $queryParams = [
            'key'     => $this->secret,
            'url'     => $websiteUrl,
        ];

        $url = $this->baseUrl . 'Tech' . '?';

        $url = $url . http_build_query($queryParams);

        $request = [
            'url'     => $url,
            'method'  => 'GET',
            'content' => [],
            'options' => ['timeout' => 120],
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
            ]
        ];

        return $this->makeRequestAndGetResponse($request);
    }

    protected function makeRequestAndGetResponse(array $request)
    {
        $response = $this->sendRequest($request);

        if ($response->status_code != 200) {
            return null;
        }

        $responseArr = json_decode($response->body, true);

        $responseBody = array_except($responseArr, ['request']);

        return $responseBody;
    }

    protected function sendRequest(array $request)
    {
        try
        {
            $this->trace->info(TraceCode::WHATCMS_API_REQUEST, $this->getTraceableRequest($request));

            $response = $this->getResponse($request);

            $this->traceResponse($response);

            return $response;
        }
        catch (Requests_Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::WHATCMS_INTEGRATION_ERROR,
                $this->getTraceableRequest($request));
        }

        return null;
    }

    /**
     * Filters request array and returns only traceable data
     *
     * @param array $request
     *
     * @return array
     */
    public function getTraceableRequest(array $request): array
    {
        $request = $this->removeQueryParamsFromUrl($request);

        return array_only($request, ['url', 'method', 'content']);
    }

    /**
     * Removing Sensitive Information from Request URL
     *
     * @param array $input
     *
     * @return array
     */
    protected function removeQueryParamsFromUrl(array $input): array
    {
        $input['url'] = strtok($input['url'], '?');

        return $input;
    }

    protected function getResponse(array $request)
    {
        $content = $request['content'];

        if (in_array($request['method'], self::JSON_METHOD))
        {
            $content = json_encode($request['content']);
        }

        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $content,
            $request['method'],
            $request['options']);

        return $response;
    }

    protected function traceResponse($response)
    {
        $responseArr = json_decode($response->body, true);

        $responseBody = array_except($responseArr, ['request']);

        $payload = [
            'status_code' => $response->status_code,
            'success'     => $response->success,
            'response'    => $responseBody,
        ];

        $this->trace->info(TraceCode::WHATCMS_API_RESPONSE, $payload);
    }
}
