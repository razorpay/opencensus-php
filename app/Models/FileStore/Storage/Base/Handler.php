<?php

namespace RZP\Models\FileStore\Storage\Base;

use App;
use RZP\Models\Base\Core;

abstract class Handler extends Core
{
    abstract public function save(array $bucket, array $fileDetails);

    abstract public function saveAs($bucket, $key, $filePath);

    /**
     * @param string      $type File Type
     * @param string      $env  Environment
     *
     * @param string|null $s3BucketConfigForExternalServices
     *
     * @return array Bucket Config containing file name and bucket region
     */
    public function getBucketConfig(string $type,
                                    string $env,
                                    string $s3BucketConfigForExternalServices = null)
    {
        if ($s3BucketConfigForExternalServices !== null)
        {
            $type = $s3BucketConfigForExternalServices;
        }

        $bucketType = Bucket::getBucketConfigName($type, $env);

        $bucketConfig = $this->config[$bucketType];

        return $bucketConfig;
    }
}
