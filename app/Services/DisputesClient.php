<?php

namespace RZP\Services;

use Request;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger;
use RZP\Constants\Environment;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\App;
use RZP\Exception\IntegrationException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class DisputesClient
{
    const CONTENT_TYPE      = 'content-type';
    const CONTENT_TYPE_JSON = 'application/json';
    const X_REQUEST_ID      = 'X-Request-ID';
    const X_MERCHANT_ID     = 'X-Merchant-ID';
    const X_AUTH_TYPE       = 'X-Auth-Type';
    const X_IS_EXPRESS      = 'X-Is-Express';

    protected $client;

    protected $options = [];

    protected $trace;

    protected $config;


    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->config = $app['config']->get('applications.disputes');

        $this->trace = $app['trace'];

        $this->client = new Guzzle([
            'base_uri' => $this->config['base_url'],
            'auth'     => [
                $this->config['auth']['username'],
                $this->config['auth']['secret'],
            ]]);
    }

    protected function formatResponse($response)
    {
        $responseArray = json_decode($response->getBody(), true);

        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_RESPONSE, [
            'response'  => $responseArray,
            'service'   => 'disputes',
        ]);

        return $responseArray['data'] ?? null;
    }

    // other headers for auth type, admin_id, etc to be added depending on the use-case.
    private function getDisputesHeaders() : array
    {
        return [
            self::CONTENT_TYPE  => 'application/json',
            self::X_REQUEST_ID  => $this->app['request']->getTaskId(),
            self::X_MERCHANT_ID => $this->app['basicauth']->getMerchantId(),
            self::X_AUTH_TYPE   => $this->app['basicauth']->getAuthType(),
            self::X_IS_EXPRESS   => $this->app['basicauth']->isExpress(),
        ];
    }

    /**
     * @throws GuzzleException
     * @throws IntegrationException
     * @throws Exception\BadRequestException
     */
    public function forwardToDisputesService()
    {
        $url = $this->config['base_url'] . Request::path();

        $this->options = [
            'headers' => $this->getDisputesHeaders(),
            'json' => Request::all(),
        ];

        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
            'url'       => $url,
            'service'   => 'disputes'
        ]);

        if ($this->app['env'] === Environment::TESTING)
        {
            return $this->options;
        }

        $response = $this->client->request(Request::method(), $url, $this->options);

        return $this->formatResponse($response);
    }
}
