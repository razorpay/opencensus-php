<?php

namespace RZP\Models\Segmentation;

use RZP\Models\Base;
use RZP\Models\FileStore\Storage\AwsS3\Handler;
use RZP\Models\FileStore\Type;
use RZP\Services\SplitzService;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function addSegment($filePath, $segmentName)
    {

        $seg_res = $this->app->splitzService->getSegmentFromName($segmentName);

        $preSignedUrl = $this->getPreSignedUrl($filePath);

        if (!isset($seg_res['response']['segment'])) {
            $response = $this->app->splitzService->createSegment($preSignedUrl, $segmentName, $filePath);
        } else {
            $segment = $seg_res['response']['segment'];
            $response = $this->app->splitzService->updateSegment($preSignedUrl, $segmentName, $segment, $filePath);
        }

        return $response;
    }

    /**
     * @param $filePath
     * @return string
     * @throws \Exception
     */
    public function getPreSignedUrl($filePath): string
    {
        $handler = new Handler();
        $env = $this->app['env'];
        $bucketConfig = $handler->getBucketConfig(Type::DATA_LAKE_SEGMENT_FILE, $env);
        $preSignedUrl = $handler->getSignedUrl($bucketConfig, $filePath);
        return $preSignedUrl;
    }

}
