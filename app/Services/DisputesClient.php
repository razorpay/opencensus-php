<?php

namespace RZP\Services;

use GuzzleHttp\RequestOptions;
use Request;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Table;
use Razorpay\Trace\Logger;
use RZP\Constants\Environment;
use RZP\Http\Request\Requests;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\App;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\RazorxTreatment;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use RZP\Http\Controllers\DisputeController;

class DisputesClient
{
    const CONTENT_TYPE          = 'content-type';
    const CONTENT_TYPE_JSON     = 'application/json';
    const X_TASK_ID             = 'X-Razorpay-TaskId';
    const X_MERCHANT_ID         = 'X-Merchant-ID';
    const X_AUTH_TYPE           = 'X-Auth-Type';
    const X_AUTH_TYPE_LIFECYCLE = 'X-Auth-Type-Lifecycle';
    const X_INTERNAL_APP        = 'X-Internal-App';
    const X_ADMIN_ID            = 'X-Admin-Id';
    const X_USER_ID             = 'X-User-Id';
    const DISPUTES_DUAL_WRITE   = "v1/disputes/dual-write";
    const MAX_RETRIES           = 1;

    const DISPUTES                  = 'disputes';
    const DISPUTE_REASONS           = 'dispute_reasons';
    const DISPUTE_EVIDENCE          = 'dispute_evidence';
    const DISPUTE_EVIDENCE_DOCUMENT = 'dispute_evidence_document';

    const AUTH_TYPE_PROXY   = 'proxy';
    const AUTH_TYPE_ADMIN   = 'admin';
    const AUTH_TYPE_PRIVATE = 'private';
    const AUTH_TYPE_EXPRESS = 'express';

    const HEADER_ACCEPT = 'Accept';
    const HEADER_ACCEPT_MIME_TYPE = [
        'text/csv',
    ];

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

    protected function formatResponse($response, $headers)
    {
        if ((isset($headers[self::HEADER_ACCEPT]) === true) &&
            (in_array($headers[self::HEADER_ACCEPT], self::HEADER_ACCEPT_MIME_TYPE) === true))
        {
            return $response;
        }

        $responseArray = json_decode($headers, true);

        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_RESPONSE, [
            'response'  => $responseArray,
            'service'   => 'disputes',
        ]);

        return $responseArray;
    }

    function getAuthType(): string
    {
        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            return self::AUTH_TYPE_PROXY;
        }
        if ($this->app['basicauth']->isExpress() === true)
        {
            return self::AUTH_TYPE_EXPRESS;
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
    private function getDisputesHeaders($additionalHeaders = []) : array
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
     * @throws \Throwable
     */
    public function forwardToDisputesService($input = null, $additionalHeaders = [])
    {
        if ($input == null)
        {
            $input = Request::all();
        }

        return $this->requestAndGetParseBody(Request::method(), Request::path(), $input, 0, $additionalHeaders);
    }

    public function fetchMultiple(string $entity, array $input)
    {
        return match ($entity) {
            self::DISPUTES, self::DISPUTE_REASONS, self::DISPUTE_EVIDENCE, self::DISPUTE_EVIDENCE_DOCUMENT =>
                $this->requestAndGetParseBody(Requests::GET, sprintf('v1/admin/%s', $entity), $input, 0),
            default => null,
        };
    }

    public function fetch(string $entity, string $id, array $input)
    {
        return match ($entity) {
            self::DISPUTES, self::DISPUTE_REASONS, self::DISPUTE_EVIDENCE, self::DISPUTE_EVIDENCE_DOCUMENT =>
            $this->requestAndGetParseBody(Requests::GET, sprintf('v1/admin/%s/%s', $entity, $id), null, 0),
            default => null,
        };
    }

    /**
     * @throws GuzzleException
     * @throws \Throwable
     */
    public function sendDualWriteToDisputesService($entityData, $table, $action)
    {
        if($this->shouldSendDualWrite($entityData, $table) === false)
        {
            return;
        }

        $this->trace->info(TraceCode::DISPUTE_DUAL_WRITE_REQUEST, [
            "table" => $table,
            "action" => $action,
            "data" => $entityData
        ]);

        try
        {
            $this->requestAndGetParseBody(Requests::POST, self::DISPUTES_DUAL_WRITE, [
                "table" => $table,
                "action" => $action,
                "data" => $entityData
            ],0);
        }
        catch(\Throwable $e)
        {
            $this->trace->count(Metric::DISPUTES_SERVICE_DUAL_WRITE_FAILED_COUNT);

            $this->trace->error(TraceCode::DISPUTES_SERVICE_DUAL_WRITE_FAILED, [
                'error_message' => $e->getMessage(),
                'table'  => $table,
                'action' => $action,
            ]);

            if ($this->app['env'] === Environment::TESTING)
            {
                throw $e;
            }
        }

    }

    protected function shouldSendDualWrite($entityData, $table): bool
    {
        $variant = $this->app['razorx']->getTreatment($table, RazorxTreatment::DISPUTES_DUAL_WRITE, $this->app['basicauth']->getMode() ?? Mode::LIVE);

        return $variant === RazorxTreatment::RAZORX_VARIANT_ON;
    }

    public function isShadowModeDualWrite($isInternationalPayment = true): bool
    {
        if ($isInternationalPayment === true)
        {
            return false;
        }

        $featureFlag = sprintf("%s_%s", RazorxTreatment::DISPUTES_DUAL_WRITE_SHADOW_MODE, $this->app['api.route']->getCurrentRouteName());

        return $this->app['razorx']->getTreatment($this->app['request']->getTaskId(), $featureFlag, $this->app['basicauth']->getMode() ?? Mode::LIVE)
            === RazorxTreatment::RAZORX_VARIANT_ON;
    }

    // reverse shadow enabled implies that dispute service will handle all the business logic, while only entity creation/updation happens on
    // API monolith
    public function isReverseShadowEnabled($merchant, $isInternationalPayment = true): bool
    {
        if ($isInternationalPayment === true)
        {
            return false;
        }

        return $merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) &&
            $this->app['razorx']->getTreatment($merchant->getId(), RazorxTreatment::DISPUTES_REVERSE_SHADOW, $this->app['basicauth']->getMode() ?? Mode::LIVE)
            === RazorxTreatment::RAZORX_VARIANT_ON;
    }

    private function is4xxException(RequestException $e): bool
    {
        return ($e->getCode() >= 400) &&
            ($e->getCode() < 500);
    }

    /**
     * @throws \Throwable
     * @throws GuzzleException
     */
    protected function requestAndGetParseBody($method, $path, $payload, $retry_count, $additionalHeaders=[])
    {
        $url = $this->config['base_url'] . $path;

        $headers = $this->getDisputesHeaders($additionalHeaders);

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
            'service'   => 'disputes',
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
            $this->trace->error(TraceCode::DISPUTES_INTEGRATION_ERROR, [
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
                $this->trace->count(Metric::DISPUTES_SERVICE_ERROR_COUNT, [
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
}
