<?php

namespace RZP\Models\Merchant\Acs\AsvClient;

use Twirp\Context;
use Twirp\Error;
use RZP\Trace\TraceCode;
use RZP\Exception\IntegrationException;
use Rzp\Accounts\Account\V1 as accountV1;
use RZP\Models\Merchant\Acs\AsvClient\Metrics\HttpClientTxn;
use \Google\Protobuf\FieldMask;

class StakeholderAsvClient extends BaseClient
{

    private $stakeholderAsvClient;

    /**
     * AccountAsvClient Constructor
     */
    function __construct($stakeholderAsvClient = null)
    {
        parent::__construct();
        if ($stakeholderAsvClient === null) {
            $this->stakeholderAsvClient = new accountV1\StakeholderAPIClient($this->host, $this->httpClient);
        } else {
            $this->stakeholderAsvClient = $stakeholderAsvClient;
        }
    }

    public function setStakeholderAsvClient($stakeholderAsvClient) {
        $this->stakeholderAsvClient = $stakeholderAsvClient;
    }

    /**
     * @throws IntegrationException
     */
    public function fetchAddressForStakeholder(string $id): accountV1\FetchMerchantStakeholdersResponse
    {
        $request = new accountV1\FetchMerchantStakeholdersRequest();
        $request->setId($id);
        $request->setFieldMask(new FieldMask([
            "paths" => ["address"]
        ]));

        return $this->sendRequestToStakeHolderFetchRoute($request);
    }

    /**
     * @throws IntegrationException
     */
    public function fetchStakeholderById(string $id): accountV1\FetchMerchantStakeholdersResponse
    {
        $request = new accountV1\FetchMerchantStakeholdersRequest();
        $request->setId($id);
        $request->setFieldMask(new FieldMask([
            "paths" => ["stakeholder"]
        ]));

        return $this->sendRequestToStakeHolderFetchRoute($request);
    }

    /**
     * @throws IntegrationException
     */
    public function fetchStakeholderByMerchantId(string $merchantId): accountV1\FetchMerchantStakeholdersResponse
    {
        $request = new accountV1\FetchMerchantStakeholdersRequest();
        $request->setMerchantId($merchantId);
        $request->setFieldMask(new FieldMask([
            "paths" => ["stakeholder"]
        ]));

        return $this->sendRequestToStakeHolderFetchRoute($request);
    }

    /**
     * @throws IntegrationException
     */
    public function sendRequestToStakeHolderFetchRoute(accountV1\FetchMerchantStakeholdersRequest $request): accountV1\FetchMerchantStakeholdersResponse
    {
        $this->trace->info(TraceCode::ASV_HTTP_CLIENT_REQUEST,
            [Constant::ROUTE_NAME => Constant::STAKEHOLDER_FETCH_ROUTE,
                'request' => ["id"=>$request->getId(),
                              "merchant_id"=>$request->getMerchantId(),
                              "field_mask" => $request->getFieldMask(),
                             ]]);

        $this->setHttpTimeoutBasedOnRoute(Constant::ASV_FETCH_ROUTE_HTTP_TIMEOUT_SEC);

        $httpClientTxnMetric = new HttpClientTxn(Constant::STAKEHOLDER_FETCH_ROUTE);

        $this->asvClientCtx = Context::withHttpRequestHeaders([], $this->headers);

        try {
            $httpClientTxnMetric->start();
            $response = $this->stakeholderAsvClient->FetchMerchantStakeholders($this->asvClientCtx, $request);
            $httpClientTxnMetric->end(true, '');

            $this->trace->info(
                TraceCode::ASV_HTTP_CLIENT_RESPONSE,
                [
                    Constant::ROUTE_NAME => Constant::STAKEHOLDER_FETCH_ROUTE
                ]
            );

            return $response;

        } catch (\Throwable $e) {
            $httpClientTxnMetric->end(true, $e->getCode());

            $this->trace->traceException($e, null, TraceCode::ASV_HTTP_CLIENT_ERROR, [
                Constant::ROUTE_NAME => Constant::STAKEHOLDER_FETCH_ROUTE,
            ]);

            throw new IntegrationException($e->getMessage());
        }
    }

    protected function setHttpTimeoutBasedOnRoute(string $route)
    {
        $timeout = 2;
        switch ($route) {
            case  Constant::STAKEHOLDER_FETCH_ROUTE:
                $timeout = floatval($this->asvConfig[Constant::ASV_FETCH_ROUTE_HTTP_TIMEOUT_SEC]);
        }
        $this->stakeholderAsvClient->setTimeout($timeout);
    }
}
