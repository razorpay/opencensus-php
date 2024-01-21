<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

use Platform\Bvs\Credencecheck\V1\TwirpError;
use Platform\Bvs\Credencecheck\V1 as CredenceV1;
use RZP\Exception\IntegrationException;
use RZP\Exception\TwirpException;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\CredenceCheckBaseResponse;

class BvsCredenceCheckClient extends BaseClient {

    private $credenceCheckClient;

    /**
     * BvsCredenceCheckClient constructor
     * @param null $merchant
     */

    function __construct($merchant = null)
    {
        parent::__construct($merchant);

        $this->credenceCheckClient = new CredenceV1\CredenceCheckAPIClient($this->host, $this->httpClient);
    }

    public function createCredenceCheck($request) : CredenceV1\CreateResponse
    {
        $this->trace->info(TraceCode::BVS_CREATE_CREDENCE_CHECK_REQUEST,[
            'type'      => $request['type'],
            'payload'   => $request['payload'],
            'metadata'  => $request['metadata']
        ]);

        $createCredenceCheckRequest = new CredenceV1\CreateRequest();

        $metadataDetails = $this->createMetadataDetails($request['metadata']);

        $createCredenceCheckRequest->setType($request['type']);
        $createCredenceCheckRequest->setMetadata($metadataDetails);
        $createCredenceCheckRequest->setPayload(get_Protobuf_Struct($request['payload']));

        try {

            $response = $this->credenceCheckClient->Create($this->apiClientCtx,$createCredenceCheckRequest);

            $this->trace->info(TraceCode::BVS_CREATE_CREDENCE_CHECK_RESPONSE,[
                'id'        => $response->getId(),
                'type'      => $request['type'],
                'status'    => $response->getStatus()
            ]);

            return $response;

        }catch (TwirpError $ex) {
            $response =  [
                'code'    => $ex->getErrorCode(),
                'msg'     => $ex->getMessage(),
                'meta'    => $ex->getMetaMap(),
            ];
            throw new TwirpException($response);
        }catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_CREATE_CREDENCE_CHECK_ERROR);

            throw new IntegrationException('
                Could not receive proper response from BVS service for Create Credence API');
        }

    }

    public function getCredenceCheckDetailsByAccountID($request) : CredenceCheckBaseResponse
    {
        $this->trace->info(TraceCode::BVS_GET_CREDENCE_DETAILS_BY_ACCOUNT_ID_REQUEST,[
            'type'      => $request['type'],
            'id'        => $request['id'],
        ]);

        $getCredenceCheckDetailsByAccountIdRequest = new CredenceV1\GetDetailsByAccountIdRequest();

        $getCredenceCheckDetailsByAccountIdRequest->setId($request['id']);
        $getCredenceCheckDetailsByAccountIdRequest->setType($request['type']);

        try {

            $response = new CredenceCheckBaseResponse($this->credenceCheckClient->GetDetailsByAccountId($this->apiClientCtx,$getCredenceCheckDetailsByAccountIdRequest));

            $this->trace->info(TraceCode::BVS_GET_CREDENCE_DETAILS_BY_ACCOUNT_ID_RESPONSE,[
                'account_id'    => $request['id'],
                'response'      => $response->getResponseData()
            ]);

            return $response;

        }catch (TwirpError $ex) {
            $response =  [
                'code'    => $ex->getErrorCode(),
                'msg'     => $ex->getMessage(),
                'meta'    => $ex->getMetaMap(),
            ];
            throw new TwirpException($response);
        }catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_GET_CREDENCE_DETAILS_BY_ACCOUNT_ID_ERROR);

            throw new IntegrationException('
                Could not receive proper response from BVS service for Get Credence Details By Account ID API');
        }

    }

    public function getCredenceCheckDetailsID($request) : CredenceV1\GetDetailsByIdResponse
    {
        $this->trace->info(TraceCode::BVS_GET_CREDENCE_DETAILS_BY_ID_REQUEST,[
            'type'              => $request['type'],
            'verification_id'   => $request['id'],
        ]);

        $getCredenceCheckDetailsRequest = new CredenceV1\GetDetailsByIdRequest();

        $getCredenceCheckDetailsRequest->setType($request['type']);
        $getCredenceCheckDetailsRequest->setId($request['id']);

        try {

            $response = $this->credenceCheckClient->GetDetailsById($this->apiClientCtx,$getCredenceCheckDetailsRequest);

            $this->trace->info(TraceCode::BVS_GET_CREDENCE_DETAILS_BY_ID_RESPONSE,[
                'verification_id'       => $response->getId(),
                'account_id'            => $response->getAccountId(),
                'status'                => $response->getStatus(),
            ]);

            return $response;

        }catch (TwirpError $ex) {
            $response =  [
                'code'    => $ex->getErrorCode(),
                'msg'     => $ex->getMessage(),
                'meta'    => $ex->getMetaMap(),
            ];
            throw new TwirpException($response);
        }catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_GET_CREDENCE_DETAILS_BY_ID_ERROR);

            throw new IntegrationException('
                Could not receive proper response from BVS service for Get Credence Details By ID API');
        }

    }

    public function createMetadataDetails($metadata) : CredenceV1\Metadata
    {
        $newMetaDataDetails = new CredenceV1\Metadata();

        $newMetaDataDetails->setCreatedBy($metadata['created_by']);
        $newMetaDataDetails->setPlatform($metadata['platform']);

        return $newMetaDataDetails;
    }

}
