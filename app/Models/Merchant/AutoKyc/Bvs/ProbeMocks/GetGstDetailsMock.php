<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\ProbeMocks;

use Rzp\Bvs\Probe\V1\GstResult;
use Google\Protobuf\Int32Value;
use Rzp\Bvs\Probe\V1\GetGstDetailsResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class GetGstDetailsMock
{
    protected $pan;

    private   $mockStatus;

    public function __construct(string $pan, string $mockStatus)
    {
        $this->pan = $pan;

        $this->mockStatus = $mockStatus;
    }

    public function getResponse(): GetGstDetailsResponse
    {
        $response = new GetGstDetailsResponse();

        switch ($this->mockStatus)
        {
            case Constant::SUCCESS:

                return $this->getSuccessResponse($response);

            default:

                return $this->getFailureResponse($response);
        }
    }

    private function getSuccessResponse(GetGstDetailsResponse $response): GetGstDetailsResponse
    {
        //initializations
        $tempResult = [];
        $count      = 0;
        $results    = [
            ["gstin" => "13AAACR5055K1ZG"],
            ["gstin" => "26AAACR5055K1Z9"]
        ];

        //creating response object
        foreach ($results as $unitResult)
        {
            $getGstDetails = new GstResult();

            $getGstDetails->setGstin($unitResult[Constant::GSTIN]);

            $count++;

            array_push($tempResult, $getGstDetails);
        }
        $response->setItems($tempResult);

        $response->setCountUnwrapped($count);

        return $response;
    }

    private function getFailureResponse(GetGstDetailsResponse $response): GetGstDetailsResponse
    {
        $response->setCountUnwrapped(0);
        return $response;
    }
}
