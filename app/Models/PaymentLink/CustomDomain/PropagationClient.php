<?php

namespace RZP\Models\PaymentLink\CustomDomain;

use Twirp\Error;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\IntegrationException;
use Rzp\CustomDomainService\Propagation\V1 as PropagationV1;

final class PropagationClient extends AbstractCDSClient implements IPropagationClient
{
    const PROPAGATED_KEY    = "propagated";
    const DOMAIN_NAME_KEY   = "domain_name";

    /**
     * @var \Rzp\CustomDomainService\Propagation\V1\PropagationAPIClient
     */
    private $api;

    public function __construct()
    {
        parent::__construct();

        $this->setApi(new PropagationV1\PropagationAPIClient($this->host, $this->httpClient));
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function checkPropagation(array $data): array
    {
        $this->trace->info(TraceCode::CDS_DOMAIN_PROPAGATION_REQUEST_RECIEVED, $data);

        try
        {
            $response = $this->api->Check($this->apiClientCtx, $this->buildDomainPropagationRequest($data));

            $serialized = $this->serializePropagationResponse($response);

            $this->trace->info(TraceCode::CDS_DOMAIN_PROPAGATION_RESPONSE_RECIEVED, $serialized);

            return $serialized;
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
     * @return \Rzp\CustomDomainService\Propagation\V1\CheckRequest
     */
    private function buildDomainPropagationRequest(array $data): PropagationV1\CheckRequest
    {
        $req = new PropagationV1\CheckRequest();

        $req->setDomainName($data[self::DOMAIN_NAME_KEY]);

        return $req;
    }

    /**
     * @param \Rzp\CustomDomainService\Propagation\V1\CheckResponse $response
     *
     * @return array
     */
    private function serializePropagationResponse(PropagationV1\CheckResponse $response): array
    {
        return [
            self::PROPAGATED_KEY    => $response->getPropagated(),
            self::DOMAIN_NAME_KEY   => $response->getDomainName()
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
