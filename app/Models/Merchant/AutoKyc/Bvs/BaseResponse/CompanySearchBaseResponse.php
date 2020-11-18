<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse;

use Rzp\Bvs\Probe\V1\CompanyResult;
use Rzp\Bvs\Probe\V1\CompanySearchResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class CompanySearchBaseResponse
{

    protected $response;

    /**
     * CompanySearchBaseResponse constructor.
     *
     * @param CompanySearchResponse $response
     */
    public function __construct(CompanySearchResponse $response)
    {
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function getCompanySearchResponse(): array
    {
        $tempResults = [];

        $responseData = null;

        if (empty($this->response->getErrorCode()) === false)
        {
            $errorData    = [
                "error" => [
                    Constant::ERROR_CODE        => $this->response->getErrorCode(),
                    Constant::ERROR_DESCRIPTION => $this->response->getErrorDescription()
                ]
            ];
            $responseData = $errorData;
        }

        else
        {
            $results = $this->response->getResults();

            foreach ($results as $companyResult)
            {

                $companyData = $this->fetchCompanyData($companyResult);

                if (empty($companyData) === false)
                {
                    array_push($tempResults, $companyData);
                }
            }

            $responseData = [Constant::RESULTS => $tempResults];
        }

        return $responseData;
    }

    private function fetchCompanyData(CompanyResult $companyResult): array
    {
        $companyData = [];

        if (empty($companyResult->getCompanyName()) === false)
        {
            $companyData[Constant::COMPANY_NAME] = $companyResult->getCompanyName();

            if (empty($companyResult->getIdentityType()) === false)
            {
                $companyData[Constant::IDENTITY_TYPE] = $companyResult->getIdentityType();
            }

            if (empty($companyResult->getIdentityType()) === false)
            {
                $companyData[Constant::IDENTITY_NUMBER] = $companyResult->getIdentityNumber();
            }
        }

        return $companyData;
    }
}
