<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

use App;
use Request;

use RZP\Error\ErrorCode;
use Twirp\Error;
use RZP\Trace\TraceCode;
use RZP\Exception\IntegrationException;
use Rzp\Bvs\ArtefactCurator\Verify\V1 as artefactcuratorV1;

class ArtefactCuratorApiClient extends BaseClient
{
    private $ArtefactCuratorApiClient;

    /**
     * $ArtefactCuratorApiClient constructor.
     *
     * @param int  $timeout
     */
    function __construct($timeout = 15)
    {
        parent::__construct();

        $this->ArtefactCuratorApiClient = new artefactcuratorV1\DigilockerAPIClientV2($this->host, $this->httpClient);

        $this->ArtefactCuratorApiClient->setTimeout($timeout);
    }

    public function getAadhaarValidation(array $payload)
    {
        $this->trace->info(TraceCode::BVS_GET_VALIDATION_REQUEST, $payload);

        $requestPayload = new artefactcuratorV1\FetchAadhaarXmlDetailsRequest();

        $validationRequest = $requestPayload->setRequestId($payload['request_id']);

        try
        {
            $response = $this->ArtefactCuratorApiClient->FetchAadhaarXmlDetails($this->apiClientCtx, $validationRequest);

            $this->trace->info(
                TraceCode::BVS_GET_VALIDATION_RESPONSE,
                [
                    'status'              =>$response->getIsValid(),
                    'artefactCuratorId'   =>$response->getArtefactCuratorId()

                ]
            );

            return $response;
        }
        catch (Error $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_INTEGRATION_ERROR, $e->getMetaMap());

            throw new IntegrationException('Could not receive proper response from BVS service',$e->getErrorCode()==='not_found'?ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND:null);
        }
    }

}
