<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse;

use Rzp\Bvs\ArtefactCurator\Probe\V1\GetGstDetailsResponse;

class GetGstDetailsArtefactCuratorBaseResponse
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
    public function getGstDetailsResponse(): array
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
