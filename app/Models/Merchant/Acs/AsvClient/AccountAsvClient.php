<?php

namespace RZP\Models\Merchant\Acs\AsvClient;

use Twirp\Context;
use Twirp\Error;
use RZP\Trace\TraceCode;
use RZP\Exception\IntegrationException;
use Rzp\Accounts\Account\V1 as accountV1;
use RZP\Models\Merchant\Acs\AsvClient\Metrics\HttpClientTxn;

class AccountAsvClient extends BaseClient
{

    private $AccountAsvClient;

    /**
     * AccountAsvClient Constructor
     */
    function __construct(accountV1\AccountAPIClient $accountAsvClient = null)
    {
        parent::__construct();
        if ($accountAsvClient === null) {
            $this->AccountAsvClient = new accountV1\AccountAPIClient($this->host, $this->httpClient);
        } else {
            $this->AccountAsvClient = $accountAsvClient;
        }
    }

    public function DeleteAccountContact(string $id, string $accountId, string $contactType)
    {
        $this->trace->info(TraceCode::ASV_HTTP_CLIENT_REQUEST,
            [Constant::ROUTE_NAME => Constant::ACCOUNT_CONTACT_DELETE_ROUTE,
                'request' => ['id' => $id, 'account_id' => $id, 'contact_type' => $contactType]]);

        // Set Timeout for Delete Account Contact Route
        $this->setHttpTimeoutBasedOnRoute(Constant::ACCOUNT_CONTACT_DELETE_ROUTE);

        $httpClientTxnMetric = new HttpClientTxn(Constant::ACCOUNT_CONTACT_DELETE_ROUTE);

        $this->asvClientCtx = Context::withHttpRequestHeaders([], $this->headers);

        try {
            $accountContactDeleteRequest = new accountV1\DeleteAccountContactRequest();
            $accountContactDeleteRequest->setId($id);
            $accountContactDeleteRequest->setAccountId($accountId);
            $accountContactDeleteRequest->setType($contactType);

            $httpClientTxnMetric->start();
            $response = $this->AccountAsvClient->DeleteAccountContact($this->asvClientCtx, $accountContactDeleteRequest);
            $httpClientTxnMetric->end(true, '');

            $this->trace->info(
                TraceCode::ASV_HTTP_CLIENT_RESPONSE,
                [
                    Constant::ROUTE_NAME => Constant::ACCOUNT_CONTACT_DELETE_ROUTE,
                    Constant::RESPONSE => $response->serializeToJsonString()
                ]
            );

            return $response;

        } catch (Error $e) {
            $httpClientTxnMetric->end(false, $e->getErrorCode());

            $this->trace->traceException($e, null, TraceCode::ASV_HTTP_CLIENT_ERROR, [
                Constant::ROUTE_NAME => Constant::ACCOUNT_CONTACT_DELETE_ROUTE,
            ]);

            throw new IntegrationException($e->getMessage());
        }
    }

    public function FetchMerchant(string $id, $fieldMask): accountV1\FetchMerchantResponse
    {
        $this->trace->info(TraceCode::ASV_HTTP_CLIENT_REQUEST, [Constant::ROUTE_NAME => Constant::MERCHANT_FETCH_ROUTE, 'request' => ['id' => $id]]);

        // Set Timeout for Fetch Merchant Route
        $this->setHttpTimeoutBasedOnRoute(Constant::MERCHANT_FETCH_ROUTE);

        $httpClientTxnMetric = new HttpClientTxn(Constant::MERCHANT_FETCH_ROUTE);

        $this->asvClientCtx = Context::withHttpRequestHeaders([], $this->headers);
        try {

            $fetchMerchantRequest = new accountV1\FetchMerchantRequest();
            $fetchMerchantRequest->setId($id);
            $fetchMerchantRequest->setFieldMask($fieldMask);

            $httpClientTxnMetric->start();
            $response = $this->AccountAsvClient->FetchMerchant($this->asvClientCtx, $fetchMerchantRequest);
            $httpClientTxnMetric->end(true, '');

            $this->trace->info(
                TraceCode::ASV_HTTP_CLIENT_RESPONSE,
                [
                    Constant::ROUTE_NAME => Constant::MERCHANT_FETCH_ROUTE,
                    Constant::RESPONSE => $response->serializeToJsonString()
                ]
            );

            return $response;

        } catch (Error $e) {
            $httpClientTxnMetric->end(false, $e->getErrorCode());

            $this->trace->traceException($e, null, TraceCode::ASV_HTTP_CLIENT_ERROR, [
                Constant::ROUTE_NAME => Constant::MERCHANT_FETCH_ROUTE,
            ]);

            throw new IntegrationException($e->getMessage());
        }
    }

    protected function setHttpTimeoutBasedOnRoute(string $route)
    {
        $timeout = 5;
        switch ($route) {
            case  Constant::ACCOUNT_CONTACT_DELETE_ROUTE:
                $timeout = floatval($this->asvConfig[Constant::ACCOUNT_CONTACT_DELETE_ROUTE_HTTP_TIMEOUT_SEC]);
            case  Constant::MERCHANT_FETCH_ROUTE:
                $timeout = floatval($this->asvConfig[Constant::ASV_FETCH_ROUTE_HTTP_TIMEOUT_SEC]);
        }
        $this->AccountAsvClient->setTimeout($timeout);
    }
}
