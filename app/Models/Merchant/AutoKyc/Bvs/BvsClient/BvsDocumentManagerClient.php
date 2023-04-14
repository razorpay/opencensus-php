<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Exception\TwirpException;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use Platform\Bvs\Kycdocumentmanager\V1\TwirpError;
use Platform\Bvs\Kycdocumentmanager\V1 as DocManagerV1;

class BvsDocumentManagerClient extends BaseClient
{
    /** @var Logger */

    private $documentManagerClient;

    /**
     * BvsProbeClient constructor.
     */
    function __construct($merchant = null)
    {
        parent::__construct($merchant);

        $this->documentManagerClient = new DocManagerV1\KYCDocumentManagerAPIClient($this->host, $this->httpClient);

    }

    public function createDocumentRecord($request): DocManagerV1\DocumentRecordResponse
    {
        $details = $request[Constant::DETAILS];

        unset($request[Constant::DETAILS]);

        $documentRequest = new DocManagerV1\CreateDocumentRecordRequest($request);

        $documentRequest->setDetails(get_Protobuf_Struct($details));

        $requestSuccess = false;

        try
        {
            $response = $this->documentManagerClient->CreateDocumentRecord($this->apiClientCtx, $documentRequest);

            $requestSuccess = true;

            $this->trace->count(Metric::BVS_CREATE_DOCUMENT_RECORD_REQUEST_TOTAL);

            $this->trace->info(
                TraceCode::BVS_CREATE_DOCUMENT_RECORD_RESPONSE,
                ['response' => $response->serializeToJsonString()]);

            return $response;
        }
        catch(TwirpError $ex)
        {
            $response =  [
                'code'    => $ex->getErrorCode(),
                'msg'     => $ex->getMessage(),
                'meta'    => $ex->getMetaMap(),
            ];

            throw new TwirpException($response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_CREATE_DOCUMENT_RECORD_ERROR);

            throw new IntegrationException('
                Could not receive proper response from BVS service for Document Manager record creation');
        }
        finally
        {
            $dimension = [
                Constant::SUCCESS => $requestSuccess,
            ];

            $this->trace->count(Metric::BVS_CREATE_DOCUMENT_RECORD_RESPONSE_TOTAL, $dimension);
        }
    }

    public function getDocumentRecord($request): DocManagerV1\DocumentRecordResponse
    {
        $getDocumentRecordRequest = new DocManagerV1\GetDocumentRecordByIdRequest($request);

        $requestSuccess = false;

        try
        {
            $response = $this->documentManagerClient->GetDocumentRecordById($this->apiClientCtx, $getDocumentRecordRequest);

            $requestSuccess = true;

            $this->trace->count(Metric::BVS_GET_DOCUMENT_RECORD_REQUEST_TOTAL);

            $this->trace->info(
                TraceCode::BVS_GET_DOCUMENT_RECORD_RESPONSE,
                ['response' => $response->serializeToJsonString()]);

            return $response;
        }
        catch(TwirpError $ex)
        {
            $response =  [
                'code'    => $ex->getErrorCode(),
                'msg'     => $ex->getMessage(),
                'meta'    => $ex->getMetaMap(),
            ];

            throw new TwirpException($response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_GET_DOCUMENT_RECORD_ERROR);

            throw new IntegrationException('
                Could not receive proper response from BVS service for retrieving document record');
        }
        finally
        {
            $dimension = [
                Constant::SUCCESS => $requestSuccess,
            ];

            $this->trace->count(Metric::BVS_GET_DOCUMENT_RECORD_RESPONSE_TOTAL, $dimension);
        }
    }
}
