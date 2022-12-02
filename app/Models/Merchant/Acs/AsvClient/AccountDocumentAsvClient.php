<?php

namespace RZP\Models\Merchant\Acs\AsvClient;

use Twirp\Context;
use Twirp\Error;
use RZP\Trace\TraceCode;
use RZP\Exception\IntegrationException;
use Rzp\Accounts\Account\V1 as accountDocumentV1;
use RZP\Models\Merchant\Acs\AsvClient\Metrics\HttpClientTxn;

class AccountDocumentAsvClient extends BaseClient
{

    private $AccountDocumentAsvClient;

    /**
     * AccountDocumentAsvClient Constructor
     */
    function __construct(accountDocumentV1\DocumentAPIClient $accountDocumenAsvClient = null)
    {
        parent::__construct();

        if ($accountDocumenAsvClient === null) {
            $this->AccountDocumentAsvClient = new accountDocumentV1\DocumentAPIClient($this->host, $this->httpClient);
        } else {
            $this->AccountDocumentAsvClient = $accountDocumenAsvClient;
        }
    }

    public function DeleteAccountDocument(string $id): accountDocumentV1\DeleteDocumentResponse
    {
        $this->trace->info(TraceCode::ASV_HTTP_CLIENT_REQUEST, [Constant::ROUTE_NAME => Constant::ACCOUNT_DOCUMENT_DELETE_ROUTE, 'request' => ['id' => $id]]);

        // Set Timeout for Delete Account Document Route
        $this->setHttpTimeoutBasedOnRoute(Constant::ACCOUNT_DOCUMENT_DELETE_ROUTE);

        $httpClientTxnMetric = new HttpClientTxn(Constant::ACCOUNT_DOCUMENT_DELETE_ROUTE);

        $this->asvClientCtx = Context::withHttpRequestHeaders([], $this->headers);
        try {

            $documentDeleteRequest = new accountDocumentV1\DeleteDocumentRequest();
            $documentDeleteRequest->setId($id);

            $httpClientTxnMetric->start();
            $response = $this->AccountDocumentAsvClient->Delete($this->asvClientCtx, $documentDeleteRequest);
            $httpClientTxnMetric->end(true, '');

            $this->trace->info(
                TraceCode::ASV_HTTP_CLIENT_RESPONSE,
                [
                    Constant::ROUTE_NAME => Constant::ACCOUNT_DOCUMENT_DELETE_ROUTE,
                    Constant::RESPONSE => $response->serializeToJsonString()
                ]
            );

            return $response;

        } catch (Error $e) {
            $httpClientTxnMetric->end(false, $e->getErrorCode());

            $this->trace->traceException($e, null, TraceCode::ASV_HTTP_CLIENT_ERROR, [
                Constant::ROUTE_NAME => Constant::ACCOUNT_DOCUMENT_DELETE_ROUTE,
            ]);

            throw new IntegrationException($e->getMessage());
        }
    }

    public function FetchMerchantDocuments(string $merchant_id): accountDocumentV1\FetchMerchantDocumentsResponse {
        $this->trace->info(TraceCode::ASV_HTTP_CLIENT_REQUEST, [Constant::ROUTE_NAME => Constant::MERCHANT_DOCUMENTS_FETCH_ROUTE, 'request' => ['merchant_id' => $merchant_id]]);

        // Set Timeout for Fetch Merchant Document Route
        $this->setHttpTimeoutBasedOnRoute(Constant::MERCHANT_DOCUMENTS_FETCH_ROUTE);

        $httpClientTxnMetric = new HttpClientTxn(Constant::MERCHANT_DOCUMENTS_FETCH_ROUTE);

        $this->asvClientCtx = Context::withHttpRequestHeaders([], $this->headers);
        try {

            $fetchMerchantDocumentsRequest = new accountDocumentV1\FetchMerchantDocumentsRequest();
            $fetchMerchantDocumentsRequest->setMerchantId($merchant_id);

            $httpClientTxnMetric->start();
            $response = $this->AccountDocumentAsvClient->FetchMerchantDocuments($this->asvClientCtx, $fetchMerchantDocumentsRequest);
            $httpClientTxnMetric->end(true, '');

            $this->trace->info(
                TraceCode::ASV_HTTP_CLIENT_RESPONSE,
                [
                    Constant::ROUTE_NAME => Constant::MERCHANT_DOCUMENTS_FETCH_ROUTE,
                    Constant::RESPONSE => $response->serializeToJsonString()
                ]
            );

            return $response;

        } catch (Error $e) {
            $httpClientTxnMetric->end(false, $e->getErrorCode());

            $this->trace->traceException($e, null, TraceCode::ASV_HTTP_CLIENT_ERROR, [
                Constant::ROUTE_NAME => Constant::MERCHANT_DOCUMENTS_FETCH_ROUTE,
            ]);

            throw new IntegrationException($e->getMessage());
        }
    }
    protected function setHttpTimeoutBasedOnRoute($route)
    {
        $timeout = 5;
        switch ($route) {
            case Constant::ACCOUNT_DOCUMENT_DELETE_ROUTE:
                $timeout = floatval($this->asvConfig[Constant::DOCUMENT_DELETE_ROUTE_HTTP_TIMEOUT_SEC]);
            case Constant::MERCHANT_DOCUMENTS_FETCH_ROUTE:
                $timeout =  floatval($this->asvConfig[Constant::ASV_FETCH_ROUTE_HTTP_TIMEOUT_SEC]);
        }
        $this->AccountDocumentAsvClient->setTimeout($timeout);
    }
}
