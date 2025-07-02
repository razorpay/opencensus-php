<?php

namespace RZP\Services;

use GuzzleHttp\RequestOptions;
use Request;
use RZP\Exception;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger;
use RZP\Constants\Environment;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\App;
use RZP\Exception\IntegrationException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class CaseManagementServiceClient
{
    const CONTENT_TYPE      = 'content-type';
    const CONTENT_TYPE_JSON = 'application/json';

    const AUTH_TYPE_PROXY   = 'proxy';
    const AUTH_TYPE_ADMIN   = 'admin';
    const AUTH_TYPE_PRIVATE = 'private';

    const X_REQUEST_ID          = 'X-Request-ID';
    const X_MERCHANT_ID         = 'X-Merchant-ID';
    const X_TASK_ID             = 'X-Razorpay-TaskId';
    const X_AUTH_TYPE           = 'X-Auth-Type';
    const X_AUTH_TYPE_LIFECYCLE = 'X-Auth-Type-Lifecycle';
    const X_INTERNAL_APP        = 'X-Internal-App';
    const X_ADMIN_ID            = 'X-Admin-Id';
    const X_USER_ID             = 'X-User-Id';

    const MAX_RETRIES = 1;

    const HEADER_ACCEPT = 'Accept';
    const HEADER_ACCEPT_MIME_TYPE = [
        'application/json',
        'text/csv',
    ];


    const UPDATE_CASE      = 'UpdateCase';

    const ROUTES_URL_MAP = [
        self::UPDATE_CASE => "/twirp/rzp.case_management_service.risk_cms.case.v1.RiskCmsCaseService/UpdateCase",
    ];

    protected $client;

    protected $options = [];

    protected $trace;

    protected $config;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->config = $app['config']->get('applications.case-management-service');

        $this->trace = $app['trace'];

        $this->client = new Guzzle([
            'base_uri' => $this->config['base_url'],
            'auth'     => [
                $this->config['auth']['username'],
                $this->config['auth']['secret'],
            ]]);
    }

    protected function formatResponse($response, $headers)
    {
        if ((isset($headers[self::HEADER_ACCEPT]) === true) &&
            (in_array($headers[self::HEADER_ACCEPT], self::HEADER_ACCEPT_MIME_TYPE) === true))
        {
            return $response;
        }

        $responseArray = json_decode($response->getBody(), true);

        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_RESPONSE, [
            'response'  => $responseArray,
            'service'   => 'case-management-service',
        ]);

        return $responseArray;
    }

    function getAuthType(): string
    {
        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            return self::AUTH_TYPE_PROXY;
        }
        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            return self::AUTH_TYPE_ADMIN;
        }
        if ($this->app['basicauth']->isPrivateAuth() === true)
        {
            return self::AUTH_TYPE_PRIVATE;
        }

        return $this->app['basicauth']->getAuthType();
    }

    // other headers for auth type, admin_id, etc to be added depending on the use-case.
    private function getCaseManagerHeaders() : array
    {
        $headers = [
            self::CONTENT_TYPE                  => 'application/json',
            self::X_TASK_ID                     => $this->app['request']->getTaskId(),
            self::X_MERCHANT_ID                 => $this->app['basicauth']->getMerchantId() ?? '',
            self::X_AUTH_TYPE                   => $this->getAuthType() ?? '',
            self::X_AUTH_TYPE_LIFECYCLE         => $this->app['basicauth']->getAuthType(),
            self::X_INTERNAL_APP                => $this->app['basicauth']->getInternalApp() ?? '',
        ];

        if (empty($additionalHeaders) === false)
        {
            $headers = array_merge($headers, $additionalHeaders);
        }

        if ($this->app['basicauth']->getAdmin() !== null)
        {
            $headers[self::X_ADMIN_ID] = $this->app['basicauth']->getAdmin()->getId() ?? '';
        }

        if ($this->app['basicauth']->getUser() !== null)
        {
            $headers[self::X_USER_ID] = $this->app['basicauth']->getUser()->getId() ?? '';
        }

        return $headers;
    }

    /**
     * @throws GuzzleException
     * @throws IntegrationException
     * @throws Exception\BadRequestException
     */
    public function forwardToCaseManagerService()
    {
        if ($input == null)
        {
            $input = Request::all();
        }

        return $this->requestAndGetParseBody(Request::method(), Request::path(), $input, 0, $additionalHeaders);
    }

    public function requestAndGetParseBody($method, $path, $payload, $retry_count=1, $additionalHeaders=[])
    {
        $url = $this->config['base_url'] . $path;

        $headers = $this->getCaseManagerHeaders($additionalHeaders);

        $this->options = [
            'headers' => $headers,
        ];

        if ($method === Requests::GET)
        {
            $url = $url . '?' ;
            if (empty($payload) === false)
            {
                $url = $url . http_build_query($payload);
            }
        }
        else
        {
            $this->options[RequestOptions::JSON] = $payload;
        }

        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
            'url'       => $url,
            'service'   => 'case-management-service',
            'payload'   => $payload,
            'headers'   => $headers,
            'retry_count' => $retry_count
        ]);

        try
        {
            $response = $this->client->request($method, $url, $this->options);

            return $this->formatResponse($response, $headers);
        }
        catch (RequestException $e)
        {
            $this->trace->error(TraceCode::CASE_MANAGEMENT_SERVICE_INTEGRATION_ERROR, [
                'error_message' => $e->getMessage(),
                'url'  => $url,
                'retries'=> $retry_count,
            ]);

            if ($e->hasResponse() && $this->is4xxException($e) === true)
            {
                $resp = $this->formatResponse($e->getResponse(), $headers);

                throw new Exception\BadRequestException($resp['error']['code'] ?? ErrorCode::BAD_REQUEST_ERROR, null, $resp['error'],
                    $resp['error']['description'] ?? '');
            }
            else
            {
                $this->trace->count(Metric::CASE_MANAGEMENT_SERVICE_ERROR_COUNT, [
                    'route' => $this->app['api.route']->getCurrentRouteName(),
                ]);

                if ($retry_count < self::MAX_RETRIES)
                {
                    return $this->requestAndGetParseBody($method, $path, $payload, $retry_count + 1);
                }

                throw $e;
            }
        }
    }

    private function is4xxException(RequestException $e): bool
    {
        return ($e->getCode() >= 400) &&
            ($e->getCode() < 500);
    }
}

