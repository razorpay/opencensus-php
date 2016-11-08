<?php

namespace RZP\Models\FileStore\Storage\Local;

use Config;
use Storage;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore\Storage\Base;

class Handler extends Base\Handler
{
    const STORAGE_DIRECTORY = 'files/file_handler/';

    public function __construct()
    {
        parent::__construct();
    }

    public function save($bucket, $name, $fullpath, $mime, $metadata = [])
    {
        $content = file_get_contents($fullpath);

        $fileName = self::STORAGE_DIRECTORY . $bucket . '/' . $name;

        Storage::put($fileName, $content);

        return $this->getStorageDir() . $fileName;
    }

    public function getBucketName($entityName)
    {
        if ($this->getMode() === 'test')
        {
            return 'rzp-test-bucket';
        }

        $bucketType = Bucket::BUCKET_MAP[$this->file->type];

        return $bucketType;
    }

    protected function getMode()
    {
        return \BasicAuth::getMode();
    }

    protected function getStorageDir()
    {
        $path = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        return $path;
    }
}
?>
