<?php

namespace RZP\Models\FileStore\Storage\Local;

use Config;
use Storage;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore\Storage\Base\Handler as BaseHandler;

class Handler extends BaseHandler
{
    const STORAGE_DIRECTORY = 'files/';

    public function save($bucket, $fileDetails)
    {
        $content = file_get_contents($fileDetails['path']);

        $fileName = self::STORAGE_DIRECTORY . $bucket . '/' . $fileDetails['key'];

        Storage::put($fileName, $content);

        return $this->getStorageDir() . $fileName;
    }

    public function getBucketName($type)
    {
        return Bucket::getBucketConfigName($type);
    }

    public function saveAs($bucket, $key, $filePath)
    {
        ;
    }

    protected function getStorageDir()
    {
        $path = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        return $path;
    }
}
?>
