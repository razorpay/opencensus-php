<?php

namespace RZP\Models\Merchant\Acs\AsvClient;

use Twirp\Context;
use Twirp\Error;
use RZP\Trace\TraceCode;
use RZP\Exception\IntegrationException;
use Rzp\Accounts\Account\V1 as accountV1;
use RZP\Models\Merchant\Acs\AsvClient\Metrics\HttpClientTxn;

class WebsiteAsvClient extends BaseClient
{

    public $merchantWebsiteClient;

    /**
     * WebsiteAsvClient Constructor
     */
    function __construct(accountV1\WebsiteAPIClient $merchantWebsiteClient = null)
    {
        parent::__construct();
        if ($merchantWebsiteClient === null) {
            $this->merchantWebsiteClient = new accountV1\WebsiteAPIClient($this->host, $this->httpClient);
        } else {
            $this->merchantWebsiteClient = $merchantWebsiteClient;
        }
    }

    /**
     * @param string $merchantId
     * @return accountV1\FetchMerchantWebsiteResponse
     * @throws IntegrationException
     */
    public function FetchMerchantWebsiteByMerchantId(string $merchantId): accountV1\FetchMerchantWebsiteResponse {
        $fetchMerchantWebsiteRequest = new accountV1\FetchAccountWebsiteRequest();
        $fetchMerchantWebsiteRequest->setAccountId($merchantId);

        return $this->FetchMerchantWebsite($fetchMerchantWebsiteRequest);
    }

    /**
     * @param string $id
     * @return accountV1\FetchMerchantWebsiteResponse
     * @throws IntegrationException
     */
    public function FetchMerchantWebsiteById(string $id): accountV1\FetchMerchantWebsiteResponse {

        $fetchMerchantWebsiteRequest = new accountV1\FetchAccountWebsiteRequest();
        $fetchMerchantWebsiteRequest->setId($id);
        return $this->FetchMerchantWebsite($fetchMerchantWebsiteRequest);
    }

    /**
     * @param accountV1\FetchAccountWebsiteRequest $fetchMerchantWebsiteRequest
     * @return accountV1\FetchMerchantWebsiteResponse
     * @throws IntegrationException
     */
    public function FetchMerchantWebsite(
        accountV1\FetchAccountWebsiteRequest $fetchMerchantWebsiteRequest): accountV1\FetchMerchantWebsiteResponse
    {
        $this->trace->info(TraceCode::ASV_HTTP_CLIENT_REQUEST, [
            Constant::ROUTE_NAME => Constant::ACCOUNT_WEBSITE_FETCH_ROUTE,
            'request' => $fetchMerchantWebsiteRequest
        ]);

        // Set Timeout for Fetch Merchant Website Route
        $this->setHttpTimeoutBasedOnRoute(Constant::ACCOUNT_WEBSITE_FETCH_ROUTE);

        $httpClientTxnMetric = new HttpClientTxn(Constant::ACCOUNT_WEBSITE_FETCH_ROUTE);

        $this->asvClientCtx = Context::withHttpRequestHeaders([], $this->headers);

        try {

            $httpClientTxnMetric->start();
            $response = $this->merchantWebsiteClient->FetchMerchantWebsite($this->asvClientCtx,
                $fetchMerchantWebsiteRequest);

            $httpClientTxnMetric->end(true, '');

            $this->trace->info(
                TraceCode::ASV_HTTP_CLIENT_RESPONSE,
                [
                    Constant::ROUTE_NAME => Constant::ACCOUNT_WEBSITE_FETCH_ROUTE,
                    'request' => $fetchMerchantWebsiteRequest
                ]
            );

            return $response;

        } catch (\Throwable $e) {
            $httpClientTxnMetric->end(false, $e->getCode());

            $this->trace->traceException($e, null, TraceCode::ASV_HTTP_CLIENT_ERROR, [
                Constant::ROUTE_NAME => Constant::ACCOUNT_WEBSITE_FETCH_ROUTE,
            ]);

            throw new IntegrationException($e->getMessage());
        }
    }

    protected function setHttpTimeoutBasedOnRoute(string $route)
    {
        $timeout = 2;
        switch($route) {
            case  Constant::ACCOUNT_WEBSITE_FETCH_ROUTE:
                $timeout = floatval($this->asvConfig[Constant::ASV_FETCH_ROUTE_HTTP_TIMEOUT_SEC]);
        }
        $this->merchantWebsiteClient->setTimeout($timeout);
    }
}
