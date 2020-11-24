<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use Rzp\Bvs\Probe\V1 as probeV1;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class BvsProbeClient extends BaseClient
{
    /** @var Logger */

    private $ProbeAPIClient;

    /**
     * BvsProbeClient constructor.
     */
    function __construct()
    {
        parent::__construct();

        $this->ProbeAPIClient = New probeV1\ProbeAPIClient($this->host, $this->httpClient);
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


}
