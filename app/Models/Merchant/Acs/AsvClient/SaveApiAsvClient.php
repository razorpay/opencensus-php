<?php

namespace RZP\Models\Merchant\Acs\AsvClient;

use Google\ApiCore\ValidationException;
use PhpParser\Node\Expr\Throw_;
use Twirp\Error;
use Twirp\Context;
use RZP\Trace\TraceCode;
use RZP\Exception\IntegrationException;
use Rzp\Accounts\Account\V1 as accountV1;
use Google\Protobuf\Struct as ProtoStruct;
use RZP\Models\Merchant\Acs\AsvClient\Metrics\HttpClientTxn;

class SaveApiAsvClient extends BaseClient
{

    private $SaveApiAsvClient;

    private $entityNameToEnumMap = [
        'merchant' => accountV1\ENTITY_NAME::merchant,
        'merchant_details' => accountV1\ENTITY_NAME::merchant_details,
        'stakeholder' => accountV1\ENTITY_NAME::stakeholder,
        'merchant_document' => accountV1\ENTITY_NAME::merchant_document,
        'merchant_website' => accountV1\ENTITY_NAME::merchant_website,
        'merchant_email' => accountV1\ENTITY_NAME::merchant_email,
    ];

    /**
     * SaveApiAsvClient Constructor
     */
    function __construct(accountV1\SaveApiClient $saveApiAsvClient = null)
    {
        parent::__construct();
        if ($saveApiAsvClient === null) {
            $this->SaveApiAsvClient = new accountV1\SaveApiClient($this->host, $this->httpClient);
        } else {
            $this->SaveApiAsvClient = $saveApiAsvClient;
        }
    }

    public function SaveEntity(string $accountId, string $entityName, array $entityValue = [])
    {
        $this->trace->info(TraceCode::ASV_HTTP_CLIENT_REQUEST,
            [Constant::ROUTE_NAME => Constant::ASV_SAVE_API_ROUTE,
                'request' => ['account_id' => $accountId, 'entity_name' => $entityName]]);

        // Check whether entityName is supported or Not
        if (!array_key_exists($entityName, $this->entityNameToEnumMap)) {
            throw new ValidationException('entity name ' . $entityName . ' is not supported');
        }

        // Set Timeout for Asv Save Api Route
        $this->setHttpTimeoutBasedOnRoute(Constant::ASV_SAVE_API_ROUTE);

        $httpClientTxnMetric = new HttpClientTxn(Constant::ASV_SAVE_API_ROUTE);

        $this->asvClientCtx = Context::withHttpRequestHeaders([], $this->headers);

        try {
            $saveApiRequest = new accountV1\SaveAccountEntityRequest();
            $saveApiRequest->setAccountId($accountId);

            $entityNameEnum = $this->entityNameToEnumMap[$entityName];
            $saveApiRequest->setEntityName($entityNameEnum);

            $entityValueProtoStruct = get_Protobuf_Struct($entityValue);
            $saveApiRequest->setEntityValue($entityValueProtoStruct);

            $httpClientTxnMetric->start();
            $response = $this->SaveApiAsvClient->Save($this->asvClientCtx, $saveApiRequest);
            $httpClientTxnMetric->end(true, '');

            $this->trace->info(
                TraceCode::ASV_HTTP_CLIENT_RESPONSE,
                [
                    Constant::ROUTE_NAME => Constant::ASV_SAVE_API_ROUTE,
                    Constant::RESPONSE => $response->serializeToJsonString()
                ]
            );

            return $response;

        } catch (Error $e) {
            $httpClientTxnMetric->end(false, $e->getErrorCode());

            $this->trace->traceException($e, null, TraceCode::ASV_HTTP_CLIENT_ERROR, [
                Constant::ROUTE_NAME => Constant::ASV_SAVE_API_ROUTE,
            ]);

            throw new IntegrationException($e->getMessage());
        }
    }

    protected function setHttpTimeoutBasedOnRoute(string $route)
    {
        $timeout = 5;
        switch ($route) {
            case  Constant::ASV_SAVE_API_ROUTE:
                $timeout = floatval($this->asvConfig[Constant::ASV_SAVE_API_ROUTE_HTTP_TIMEOUT_SEC]);
        }
        $this->SaveApiAsvClient->setTimeout($timeout);
    }

}

