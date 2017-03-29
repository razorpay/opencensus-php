<?php

namespace RZP\Models\FileStore\Storage\Base;

use App;
use RZP\Models\Base\Core;

abstract class Handler extends Core
{
    abstract public function save(array $bucket, array $fileDetails);

    abstract public function saveAs($bucket, $key, $filePath);

    /**
     * @param string $type File Type
     * @param string $env  Environment
     *
     * @return array Bucket Config conating file name and bucket region
     */
    public function getBucketConfig(string $type, string $env)
    {
        $bucketType = Bucket::getBucketConfigName($type, $env);

        $bucketConfig = $this->config[$bucketType];

        return $bucketConfig;
    }
}
