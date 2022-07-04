<?php

namespace RZP\Models\PaymentLink\CustomDomain;

use Twirp\Error;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;
use Rzp\CustomDomainService\Domain\V1 as DomainV1;

final class DomainClient extends AbstractCDSClient implements IDomainClient
{
    const ID_KEY            = "id";
    const COUNT_KEY         = "count";
    const ITEMS_KEY         = "items";
    const ENTITY_KEY        = "entity";
    const STATUS_KEY        = "status";
    const DOMAIN_ID_KEY     = "domain_id";
    const DOMAIN_NAME_KEY   = "domain_name";
    const MERCHANT_ID_KEY   = "merchant_id";
    const IS_SUB_DOMAIN_KEY = "is_sub_domain";
    const CREATED_AT_KEY    = "created_at";
    const UPDATED_AT_KEY    = "updated_at";

    /**
     * @var \Rzp\CustomDomainService\Domain\V1\DomainAPIClient
     */
    private $api;

    public function __construct()
    {
        parent::__construct();

        $this->setApi(new DomainV1\DomainAPIClient($this->host, $this->httpClient));
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function createDomain(array $data): array
    {
        $this->trace->info(TraceCode::CDS_CREATE_DOMAIN_REQUEST_RECIEVED, $data);

        try
        {
            $response = $this->getApi()->Create($this->apiClientCtx, $this->buildDomainRequest($data));

            $this->trace->info(TraceCode::CDS_CREATE_DOMAIN_RESPONSE_RECIEVED, [
                self::STATUS_KEY    => $response->getStatus(),
                self::DOMAIN_ID_KEY => $response->getId(),
            ]);

            return $this->serializeDomainResponse($response);
        }
        catch (Error $e)
        {
            $this->trace->traceException($e, null, TraceCode::CDS_INTEGRATION_ERROR, $e->getMetaMap());

            throw new IntegrationException('Could not receive proper response from custom domain service', $e->getErrorCode()==='not_found'?ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:null);
        }
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function listDomain(array $data): array
    {
        $this->trace->info(TraceCode::CDS_LIST_DOMAIN_REQUEST_RECIEVED, $data);

        try
        {
            $response = $this->getApi()->List($this->apiClientCtx, $this->buildDomainListRequest($data));

            $this->trace->info(TraceCode::CDS_LIST_DOMAIN_RESPONSE_RECIEVED, [
                self::COUNT_KEY => $response->getCount(),
            ]);

            return $this->serializeListDomainResponse($response);
        }
        catch (Error $e)
        {
            $this->trace->traceException($e, null, TraceCode::CDS_INTEGRATION_ERROR, $e->getMetaMap());

            throw new IntegrationException('Could not receive proper response from custom domain service', $e->getErrorCode()==='not_found'?ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:null);
        }
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function deleteDomain(array $data): array
    {
        $this->trace->info(TraceCode::CDS_DELETE_DOMAIN_REQUEST_RECIEVED, $data);

        try
        {
            $response = $this->getApi()->Delete($this->apiClientCtx, $this->buildDomainRequest($data));

            $this->trace->info(TraceCode::CDS_DELETE_DOMAIN_RESPONSE_RECIEVED, [
                self::DOMAIN_NAME_KEY => $response->getDomainName(),
            ]);

            return $this->serializeDomainResponse($response);
        }
        catch (Error $e)
        {
            $this->trace->traceException($e, null, TraceCode::CDS_INTEGRATION_ERROR, $e->getMetaMap());

            throw new IntegrationException('Could not receive proper response from custom domain service', $e->getErrorCode()==='not_found'?ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:null);
        }
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function isSubDomain(array $data): array
    {
        $this->trace->info(TraceCode::CDS_IS_SUBDOMAIN_REQUEST_RECIEVED, $data);

        try
        {
            $response = $this->getApi()->IsSubDomain($this->apiClientCtx, $this->buildDomainRequest($data));

            return $this->serializeIsDomainResponse($response);
        }
        catch (Error $e)
        {
            $this->trace->traceException($e, null, TraceCode::CDS_INTEGRATION_ERROR, $e->getMetaMap());

            throw new IntegrationException('Could not receive proper response from custom domain service', $e->getErrorCode()==='not_found'?ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:null);
        }
    }

    /**
     * @param array $data
     *
     * @return \Rzp\CustomDomainService\Domain\V1\DomainRequest
     */
    private function buildDomainRequest(array $data): DomainV1\DomainRequest
    {
        $req = new DomainV1\DomainRequest();

        $req->setDomainName($data[self::DOMAIN_NAME_KEY]);
        $req->setMerchantId($data[self::MERCHANT_ID_KEY]);

        return $req;
    }

    /**
     * @param array $data
     *
     * @return \Rzp\CustomDomainService\Domain\V1\ListDomainRequest
     */
    private function buildDomainListRequest(array $data): DomainV1\ListDomainRequest
    {
        $req = new DomainV1\ListDomainRequest();

        if (array_has($data, self::MERCHANT_ID_KEY))
        {
            $req->setMerchantId($data[self::MERCHANT_ID_KEY]);
        }

        if (array_has($data, self::DOMAIN_NAME_KEY))
        {
            $req->setDomainName($data[self::DOMAIN_NAME_KEY]);
        }

        return $req;
    }

    /**
     * @param $response
     *
     * @return array
     */
    private function serializeDomainResponse($response): array
    {
        return [
            self::ID_KEY            => $response->getId(),
            self::DOMAIN_NAME_KEY   => $response->getDomainName(),
            self::MERCHANT_ID_KEY   => $response->getMerchantId(),
            self::STATUS_KEY        => $response->getStatus(),
        ];
    }

    /**
     * @param \Rzp\CustomDomainService\Domain\V1\ListDomainResponse $response
     *
     * @return array
     */
    private function serializeListDomainResponse(DomainV1\ListDomainResponse $response): array
    {
        $res = [
            self::COUNT_KEY     => $response->getCount(),
            self::ENTITY_KEY    => $response->getEntity(),
            self::ITEMS_KEY     => []
        ];

        foreach ($response->getItems() as $item)
        {
            $res[self::ITEMS_KEY][] = $this->serializeDomainResponse($item);
        }

        return $res;
    }

    /**
     * @param \Rzp\CustomDomainService\Domain\V1\IsSubDomainResponse $response
     *
     * @return array
     */
    private function serializeIsDomainResponse(DomainV1\IsSubDomainResponse $response): array
    {
        return [
            self::IS_SUB_DOMAIN_KEY => $response->getIsSubDomain(),
        ];
    }

    public function setApi($client)
    {
        $this->api = $client;
    }

    public function getApi()
    {
        return $this->api;
    }
}
