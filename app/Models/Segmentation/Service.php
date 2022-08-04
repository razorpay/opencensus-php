<?php

namespace RZP\Models\Segmentation;

use Exception;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base;
use RZP\Models\FileStore\Type;
use RZP\Trace\TraceCode;

class Service extends Base\Service{

    const FALSE_POSITIVITY_RATE = "0.0001";
    const DATA_LAKE_SEGMENT_TYPE = Type::DATA_LAKE_SEGMENTS_BUCKET_CONFIG;

    public function segmentPopulate(array $input)
    {
        try {

            //path: datalakesegments/update/GMV_GT_THAN_1LAKH/mids.csv
            $filePath = $input['path'];
            if(strpos($filePath,"/_temporary") || strpos($filePath, "/_committed_")){
                return [];
            }

            $this->trace->info(TraceCode::SPLITZ_SEGMENT_LAMBDA_REQUEST, ['input' => $input]);

            $segmentName = $this->getSegmentFromPath($filePath);

            return $this->core()->addSegment($filePath, $segmentName);

        } catch (Exception $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SEGMENT_CREATE_UPDATE_ERROR,
                [
                    'input' => $input,
                ]);

            return [
                'transaction_id' => null,
                'duplicate' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getSegmentFromPath($filePath): string
    {
        $pathArray = explode("/", $filePath);
        return $pathArray[count($pathArray) - 2];
    }


}
