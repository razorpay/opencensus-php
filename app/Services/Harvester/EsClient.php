<?php

namespace RZP\Services\Harvester;

use App;
use RZP\Http\Request\Requests;

use RZP\Trace\TraceCode;
use RZP\Exception\RuntimeException;

class EsClient
{
    protected $mode;

    protected $mock;

    protected $trace;

    protected $config;

    protected $accessToken;

    protected $queryBaseUrl;

    const REQUEST_TIMEOUT = 20;

    const ENDPOINT        = 'elasticsearch';

    public function __construct()
    {
        $app                = App::getFacadeRoot();

        $config             = $app->config->get('applications.harvester');

        $this->queryBaseUrl = $config['url'];

        $this->mode         = $app['rzp.mode'];

        $this->accessToken  = $config['analytics_token'];

        $this->trace        = $app['trace'];
    }

    public function search(array $esQuery)
    {
        // Creates POST body as required by Harvester from ES Query
        $query = [
            'index' => $esQuery['index'],
            'query' => $esQuery['body'],
            'mode'  => $this->mode
        ];

        return $this->prepareAndSendRequest(self::ENDPOINT, $query);
    }

    protected function prepareAndSendRequest(string $urlPath, array $data)
    {
        $request = $this->prepareRequest($urlPath, $data);

        $response = null;

        try
        {
            $response = $this->sendRequest($request);

            $this->validateResponse($response);
        }
        catch(\WpOrg\Requests\Exception $e)
        {
            $this->trace->info(
                TraceCode::HARVESTER_FAILURE,
                [
                    'message' => $e->getMessage(),
                    'type'    => $e->getType(),
                    'data'    => $e->getData()
                ]);
        }

        return json_decode($response->body, true);
    }

    protected function validateResponse($response)
    {
        $this->trace->info(
            TraceCode::HARVESTER_RESPONSE,
            [
                'response' => substr($response->body, 0, 500),
            ]);

        if ($response->status_code !== 200)
        {
            throw new RuntimeException('Unexpected response received from harvester service');
        }
    }

    protected function prepareRequest(string $urlPath, array $data)
    {
        $request = [
            'url'           => $this->queryBaseUrl . $urlPath,
            'method'        => 'POST',
            'content'       => json_encode($data),
            'content-type'  => 'application/json',
        ];

        // The location of the trace is very critical here.
        // Be careful moving this code. Don't trace headers containing signature
        $this->trace->info(
            TraceCode::HARVESTER_REQUEST,
            [
                'path'    => $urlPath,
                'data'    => $data,
                'request' => $request
            ]);

        $headers = [
            'x-signature'   => $this->accessToken,
            'Accept'        => 'application/json'
        ];

        $options['timeout'] = self::REQUEST_TIMEOUT;

        $request['headers'] = $headers;

        $request['options'] = $options;

        return $request;
    }

    protected function sendRequest($request)
    {
        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['method'],
            $request['options']);

        return $response;
    }
}
