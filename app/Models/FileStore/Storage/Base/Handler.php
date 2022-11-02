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
     * @param string|null $s3BucketConfigForExternalServices
     * @param bool        $shouldCheckEnv This boolean value mentions whether to check for testing or production
     *                                    environment o assign default bucket name
     * @return array Bucket Config containing file name and bucket region
     */
    public function getBucketConfig(string $type,
                                    string $env,
                                    string $s3BucketConfigForExternalServices = null,
                                    bool $shouldCheckEnv = true)
    {
        if ($s3BucketConfigForExternalServices !== null)
        {
            $type = $s3BucketConfigForExternalServices;
        }

        $bucketType = Bucket::getBucketConfigName($type, $env, $shouldCheckEnv);

        $bucketConfig = $this->config[$bucketType];

        return $bucketConfig;
    }
}
