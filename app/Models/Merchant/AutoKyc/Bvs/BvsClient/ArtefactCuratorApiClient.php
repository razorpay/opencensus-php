<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

use App;
use Request;

use RZP\Error\ErrorCode;
use Twirp\Error;
use RZP\Trace\TraceCode;
use RZP\Exception\TwirpException;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use Rzp\Bvs\ArtefactCurator\Verify\V1\TwirpError;
use Rzp\Bvs\ArtefactCurator\Verify\V1 as artefactcuratorV1;

class ArtefactCuratorApiClient extends BaseClient
{
    private $ArtefactCuratorApiClient;

    /**
     * $ArtefactCuratorApiClient constructor.
     *
     * @param int  $timeout
     */
    function __construct($timeout = 5)
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

    /**
     * @param array $payload
     *
     * @return array
     * @throws IntegrationException
     */
    public function getVerificationUrl(array $payload)
    {
        $this->trace->info(TraceCode::BVS_GET_VERIFICATION_URL_REQUEST, $payload);

        $requestSuccess = false;

        $requestPayload = new artefactcuratorV1\GetDigilockerUrlRequest($payload);

        try
        {
            $response = $this->ArtefactCuratorApiClient->GetDigilockerUrl($this->apiClientCtx, $requestPayload);

            $this->trace->info(
                TraceCode::BVS_GET_VERIFICATION_URL_RESPONSE,
                [
                    'response' => $response->getUrl(),
                ]
            );

            $requestSuccess = true;

            return [
                'verification_url' => $response->getUrl()
            ];
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
            $this->trace->traceException($e, null, TraceCode::BVS_INTEGRATION_ERROR, $e->getMetaMap());

            throw new IntegrationException('
                Could not receive proper response from BVS service');
        }
        finally
        {
            $dimension = [
                Constant::SUCCESS => $requestSuccess,
            ];

            $this->trace->count(Metric::BVS_DIGILOCKER_URL_RESPONSE_TOTAL, $dimension);
        }
    }

    /**
     * @param array $payload
     *
     * @return array
     * @throws IntegrationException
     */
    public function fetchVerificationDetails(array $payload)
    {
        $this->trace->info(TraceCode::BVS_GET_VERIFICATION_DETAILS_REQUEST, $payload);

        $requestSuccess = false;

        $requestPayload = new artefactcuratorV1\FetchAadhaarDetailsRequest($payload);

        try
        {
            $response = $this->ArtefactCuratorApiClient->FetchAadhaarDetails($this->apiClientCtx, $requestPayload);

            $this->trace->info(
                TraceCode::BVS_GET_VERIFICATION_DETAILS_RESPONSE,
                [
                    'response' => $response->getIsValid(),
                ]
            );

            $requestSuccess = true;

            return [
                'is_valid' => $response->getIsValid(),
                'probe_id' => $response->getArtefactCuratorId(),
                'file_url' => $response->getFileUrl()
            ];
        }
        catch (TwirpError $ex)
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
            $this->trace->traceException($e, null, TraceCode::BVS_INTEGRATION_ERROR, $e->getMetaMap());

            throw new IntegrationException('
                Could not receive proper response from BVS service');
        }
        finally
        {
            $dimension = [
                Constant::SUCCESS => $requestSuccess,
            ];

            $this->trace->count(Metric::BVS_DIGILOCKER_DETAILS_RESPONSE_TOTAL, $dimension);
        }
    }
}
