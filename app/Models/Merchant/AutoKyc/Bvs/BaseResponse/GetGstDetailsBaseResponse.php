<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse;

use Rzp\Bvs\Probe\V1\GstResult;
use Rzp\Bvs\Probe\V1\GetGstDetailsResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class GetGstDetailsBaseResponse
{

    protected $response;

    /**
     * GetGstDetailsBaseResponse constructor.
     *
     * @param GetGstDetailsResponse $response
     */
    public function __construct(GetGstDetailsResponse $response)
    {
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function geGstDetailsResponse(): array
    {
        $tempResults = [];

        $count = $this->response->getCountUnwrapped();

        if($count>0)
        {
            $results = $this->response->getItems();

            foreach ($results as $gstResult)
            {

                if (empty($gstResult->getGstin()) === false)
                {
                    array_push($tempResults, $gstResult->getGstin());
                }
            }
        }

        return $tempResults;
    }
}
