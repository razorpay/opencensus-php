<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\ProbeMocks;

use Rzp\Bvs\Probe\V1\CompanyResult;
use Rzp\Bvs\Probe\V1\CompanySearchResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class CompanySearchMock
{
    protected $searchString;

    private   $mockStatus;

    public function __construct(string $search_string, string $mockStatus)
    {
        $this->$search_string = $search_string;

        $this->mockStatus = $mockStatus;
    }

    public function getResponse(): CompanySearchResponse
    {
        $response = new CompanySearchResponse();

        switch ($this->mockStatus)
        {

            case Constant::SUCCESS:

                return $this->getSuccessResponse($response);

            default:

                return $this->getFailureResponse($response);
        }
    }

    private function getSuccessResponse(CompanySearchResponse $response): CompanySearchResponse
    {
        $results    = [
            [
                'company_name'    => 'ABC MATRIMONIALS.COM LIMITED',
                'identity_number' => 'U74899DL2000PLC105536',
                'identity_type'   => 'cin'
            ],
            [
                'company_name'    => 'ABC IMAGINATION PRIVATE LIMITED',
                'identity_number' => 'U74899DL1995PTC074932',
                'identity_type'   => 'cin'
            ],
            [
                'company_name'    => 'ABC TEA WORKERS WELFARE SERVICES',
                'identity_number' => 'U15311WB1968NPL027334',
                'identity_type'   => 'cin'
            ],
            [
                'company_name'    => 'ABC INDUSTRIAL INFRA-MANAGEMENT PRIVATE LIMITED',
                'identity_number' => 'U45400GJ2000PTC037720',
                'identity_type'   => 'cin'
            ],
            [
                'company_name'    => 'ABC TRAVEL AND FOREX INDIA PRIVATE LIMITED',
                'identity_number' => 'U63040MH1999PTC119454',
                'identity_type'   => 'cin'
            ],
            [
                'company_name'    => 'ABC COMMODITIES LTD.',
                'identity_number' => 'U67120WB1994PLC064845',
                'identity_type'   => 'cin'
            ],
            [
                'company_name'    => 'ABC EDUMED LLP',
                'identity_number' => 'AAM-1477',
                'identity_type'   => 'llpin'
            ],
            [
                'company_name'    => 'ABC EMPORIO LLP',
                'identity_number' => 'AAG-2428',
                'identity_type'   => 'llpin'
            ]
        ];
        $tempResult = [];

        foreach ($results as $unitResult)
        {
            $companyResult = new CompanyResult();

            $companyResult->setCompanyName($unitResult[Constant::COMPANY_NAME]);

            $companyResult->setIdentityNumber($unitResult[Constant::IDENTITY_NUMBER]);

            $companyResult->setIdentityType($unitResult[Constant::IDENTITY_TYPE]);

            array_push($tempResult, $companyResult);
        }
        $response->setResults($tempResult);

        return $response;
    }

    private function getFailureResponse(CompanySearchResponse $response): CompanySearchResponse
    {
        $response->setErrorCode("internal");

        $response->setErrorDescription("hystrix: timeout");

        return $response;
    }
}
