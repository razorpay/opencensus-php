<?php

namespace RZP\Services;

use App;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Constants\Environment;
use Psr\Http\Message\RequestInterface;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;

class CapitalEarlySettlementClient
{
    const ONDEMAND_STATUS_UPDATE_ENDPOINT = 'early_settlements/ondemand_triggers';

    const ENABLE_SCHEDULED_ES = 'instant_settlements/scheduled/enable';

    const UPSERT_MERCHANT_FEATURE_CONFIG = 'feature_configs/merchant';

    const GET_FEATURE_CONFIG = 'feature_configs';

    const GET_MERCHANT_BALANCE_BY_TYPE = 'balances?type=%s';

    const GET_FUND_ACCOUNT = 'fund_accounts';

    const DUAL_WRITE_FUND_ACCOUNT = 'fund_accounts/dual_write';

    protected $app;

    protected $trace;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config'];
    }

    public function pushSettlementOndemandStatusUpdate($settlementOndemandTriggerId, $event, $amount)
    {

        return $this->sendRequestAndParseResponse(self::ONDEMAND_STATUS_UPDATE_ENDPOINT,
            $this->getOndemandTriggerUpdateData($settlementOndemandTriggerId, $event, $amount),
            ['X-Auth-Type'    => 'direct',
             'X-Service-Name' => 'api'
            ],
            'PATCH'
        );
    }

    protected function getOndemandTriggerUpdateData($settlementOndemand, $event, $amount) : array
    {
        return [
            'settlement_ondemand_id'         => $settlementOndemand->getId(),
            'settlement_ondemand_trigger_id' => $settlementOndemand->getSettlementOndemandTriggerId(),
            'settlement_ondemand_status'     => $settlementOndemand->getStatus(),
            'event'                          => $event,
            'amount'                         => $amount
        ];
    }

    public function enableScheduledEs($merchantId, $uerRole, $skipRoleCheck)
    {
        return $this->sendRequestAndParseResponse(self::ENABLE_SCHEDULED_ES,
                                                  [
                                                        'merchant_id' => $merchantId,
                                                        'user_role'   => $uerRole,
                                                        'skip_user_role_check' => $skipRoleCheck
                                                  ],
                                                  [
                                                        'X-Auth-Type'    => 'direct',
                                                        'X-Service-Name' => 'api'
                                                  ],
                                                  'POST'
        );
    }

    public function createMerchantFeatureConfig($request)
    {
        return $this->sendRequestAndParseResponse(self::UPSERT_MERCHANT_FEATURE_CONFIG,
            $request,
            [
                'X-Auth-Type'    => 'internal',
                'X-Service-Name' => 'api'
            ],
            'POST'
        );
    }

    public function updateMerchantFeatureConfig($request)
    {
        return $this->sendRequestAndParseResponse(self::UPSERT_MERCHANT_FEATURE_CONFIG,
            $request,
            [
                'X-Auth-Type'    => 'internal',
                'X-Service-Name' => 'api'
            ],
            'PATCH'
        );
    }

    public function getFeatureConfig($type, $merchantId=null)
    {
        $url = self::GET_FEATURE_CONFIG . '?' . http_build_query(['merchant_id' => $merchantId, 'type' => $type]);

        return $this->sendRequestAndParseResponse($url,
            [],
            [
                'X-Auth-Type'    => 'internal',
                'X-Service-Name' => 'api'
            ],
            'GET'
        );
    }

    public function getMerchantBalanceByType($type, $merchantId)
    {
        $url = sprintf(self::GET_MERCHANT_BALANCE_BY_TYPE, $type);

        return $this->sendRequestAndParseResponse($url,
            [],
            [
                'X-Auth-Type'    => 'internal',
                'X-Service-Name' => 'api',
                'X-Merchant-Id' => $merchantId
            ],
            'GET'
        );
    }
      
    public function getFundAccount($merchantId)
    {
        $url = self::GET_FUND_ACCOUNT . '?' . http_build_query(['merchant_id' => $merchantId]);

        return $this->sendRequestAndParseResponse($url,
            [],
            [
                'X-Auth-Type'    => 'internal',
                'X-Service-Name' => 'api'
            ],
            'GET'
        );
    }

    public function invalidateAndCreateFundAccount($merchantId, $skipCreation = false, $syncMode = false, $bankAccount = null)
    {
        return $this->sendRequestAndParseResponse(self::GET_FUND_ACCOUNT,
            [
                'merchant_id' => $merchantId,
                'skip_creation' => $skipCreation,
                'sync_mode' => $syncMode,
                'bank_account' => $bankAccount,
            ],
            [
                'X-Auth-Type'    => 'internal',
                'X-Service-Name' => 'api'
            ],
            'POST'
        );
    }

    public function dualWriteFundAccount($request)
    {
        return $this->sendRequestAndParseResponse(self::DUAL_WRITE_FUND_ACCOUNT,
            $request,
            [
                'X-Auth-Type'    => 'internal',
                'X-Service-Name' => 'api'
            ],
            'PUT'
        );
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        string $method,
        array $options = [])
    {
        $config                  = config('applications.capital_es');
        $baseUrl                 = $config['url'];
        $username                = $config['username'];
        $password                = $config['secret'];

        $defaultHeaders = $headers + [
                'Accept'            => 'application/json',
                'Content-Type'      => 'application/json',
                'X-Task-Id'         => $this->app['request']->getTaskId(),
                'Authorization'     => 'Basic '. base64_encode($username . ':' . $password),
            ];

        $devstackLabel = $this->app['request']->header(RequestHeader::DEV_SERVE_USER);
        if ($this->app['env'] !== Environment::PRODUCTION && empty($devstackLabel) === false)
        {
            $defaultHeaders[RequestHeader::DEV_SERVE_USER] = $devstackLabel;
        }

        return $this->sendRequest($defaultHeaders, $baseUrl . $url, $method, empty($body) ? '' : json_encode($body));
    }

    protected function sendRequest($headers, $url, $method, $body)
    {
        $this->trace->debug(TraceCode::CAPITAL_ES_SERVICE_REQUEST, [
            'url'     => $url,
            'method'  => $method,
            'body'    => $body,
            'headers' => $headers,
        ]);

        $req = $this->newRequest($headers, $url, $method, $body , 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        if($resp->getStatusCode() != 200)
        {
            $this->trace->warning(TraceCode::CAPITAL_ES_SERVICE_RESPONSE, [
                'status_code'   => $resp->getStatusCode(),
                'body'          => $resp->getBody(),
            ]);
        }

        if ($resp->getStatusCode() >= 500)
        {
            throw new ServerErrorException('could not complete request', ErrorCode::SERVER_ERROR);
        }
        else if($resp->getStatusCode() >= 400)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,null,
                [
                    'status_code' => $resp->getStatusCode(),
                    'body' => $resp->getBody(),
                ]);
        }
        else
        {
            $this->trace->debug(TraceCode::CAPITAL_ES_SERVICE_RESPONSE, [
                'status_code'   => $resp->getStatusCode(),
            ]);
        }

        return json_decode($resp->getBody(), true);
    }

    private function newRequest(array $headers, string $url, string $method, string $reqBody, string $contentType):
    RequestInterface
    {
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();

        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        $body = $streamFactory->createStream($reqBody);

        $req = $requestFactory->createRequest($method, $url);

        foreach ($headers as $key => $value) {
            $req = $req->withHeader($key, $value);
        }

        return $req
            ->withBody($body)
            ->withHeader('Accept', $contentType)
            ->withHeader('Content-Type', $contentType);
    }
}
