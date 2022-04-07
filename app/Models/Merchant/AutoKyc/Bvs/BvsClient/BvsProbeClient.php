<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use Rzp\Bvs\Probe\V1 as probeV1;
use Rzp\Bvs\ArtefactCurator\Probe\V1 as artefactCuratorProbeV1;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class BvsProbeClient extends BaseClient
{
    /** @var Logger */

    private $ProbeAPIClient;

    private $ArtefactCuratorProbeAPIClient;

    /**
     * BvsProbeClient constructor.
     */
    function __construct()
    {
        parent::__construct();

        $this->ProbeAPIClient = new probeV1\ProbeAPIClient($this->host, $this->httpClient);

        $this->ArtefactCuratorProbeAPIClient = new artefactCuratorProbeV1\ProbeAPIClient($this->host, $this->httpClient);

    }


    /**
     * @param string $searchString
     *
     * @return probeV1\CompanySearchResponse
     * @throws IntegrationException
     */
    public function companySearch(string $searchString): probeV1\CompanySearchResponse
    {
        $companySearchRequest = new probeV1\CompanySearchRequest();

        $companySearchRequest->setSearchString($searchString);

        $requestSuccess = false;

        try
        {
            $response = $this->ProbeAPIClient->GetCompanySearch($this->apiClientCtx, $companySearchRequest);

            $requestSuccess = true;

            $this->trace->count(Metric::BVS_COMPANY_SEARCH_REQUEST_TOTAL);

            $this->trace->info(
                TraceCode::BVS_COMPANY_SEARCH_RESPONSE,
                ['response' => $response->serializeToJsonString()]);

            return $response;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_COMPANY_SEARCH_ERROR);

            throw new IntegrationException('
                Could not receive proper response from BVS service for Company Search');
        }
        finally
        {
            $dimension = [
                Constant::SUCCESS => $requestSuccess,
            ];

            $this->trace->count(Metric::BVS_COMPANY_SEARCH_RESPONSE_TOTAL, $dimension);
        }
    }

    /**
     * @param string $pan
     * @param string|null $authStatus
     *
     * @return probeV1\GetGstDetailsResponse
     * @throws IntegrationException
     */
    public function getGstDetails(string $pan, ?string $authStatus = null): probeV1\GetGstDetailsResponse
    {
        $getGstDetailsRequest = new probeV1\GetGstDetailsRequest();

        $getGstDetailsRequest->setPan($pan);

        if (empty($authStatus) === false)
        {
            $getGstDetailsRequest->setAuthStatus($authStatus);
        }

        $requestSuccess = false;

        try
        {
            $response = $this->ProbeAPIClient->GetGstDetails($this->apiClientCtx, $getGstDetailsRequest);

            $requestSuccess = true;

            $this->trace->count(Metric::BVS_GET_GST_DETAILS_REQUEST_TOTAL);

            $this->trace->info(
                TraceCode::BVS_GET_GST_DETAILS_RESPONSE,
                ['response' => $response->serializeToJsonString()]);

            return $response;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_GET_GST_DETAILS_ERROR);

            throw new IntegrationException('
                Could not receive proper response from BVS service for gst details');
        }
        finally
        {
            $dimension = [
                Constant::SUCCESS => $requestSuccess,
            ];

            $this->trace->count(Metric::BVS_GET_GST_DETAILS_RESPONSE_TOTAL, $dimension);
        }
    }

    /**
     * @param string $pan
     * @param string|null $authStatus
     *
     * @return artefactCuratorProbeV1\GetGstDetailsResponse
     * @throws IntegrationException
     */
    public function artefactCuratorGetGstDetails(string $pan, ?string $authStatus = null): artefactCuratorProbeV1\GetGstDetailsResponse
    {
        $getGstDetailsRequest = new artefactCuratorProbeV1\GetGstDetailsRequest();

        $getGstDetailsRequest->setPan($pan);

        if (empty($authStatus) === false)
        {
            $authStatusPayload = new artefactCuratorProbeV1\AuthStatusFilter();

            $authStatusPayload->setValue($authStatus);

            $authStatusPayload->setIncludeNull(true);

            $getGstDetailsRequest->setAuthStatus($authStatusPayload);
        }

        $requestSuccess = false;

        try
        {
            $response = $this->ArtefactCuratorProbeAPIClient->GetGstDetails($this->apiClientCtx, $getGstDetailsRequest);

            $requestSuccess = true;

            $this->trace->count(Metric::BVS_GET_GST_DETAILS_REQUEST_TOTAL);

            $this->trace->info(
                TraceCode::BVS_GET_GST_DETAILS_RESPONSE,
                ['response' => $response->serializeToJsonString()]);

            return $response;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_GET_GST_DETAILS_ERROR);

            throw new IntegrationException('
                Could not receive proper response from BVS service for gst details');
        }
        finally
        {
            $dimension = [
                Constant::SUCCESS => $requestSuccess,
            ];

            $this->trace->count(Metric::BVS_GET_GST_DETAILS_RESPONSE_TOTAL, $dimension);
        }
    }
}
