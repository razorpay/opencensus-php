<?php

namespace RZP\Models\FileStore\Storage\Local;

use Config;
use Storage;

use RZP\Models\FileStore\Storage\Base\Handler as BaseHandler;

class Handler extends BaseHandler
{
    /**
     * Directory prefix where all files should be stored
     */
    const STORAGE_DIRECTORY = 'files/';

    /*
     * config for the Handler instance
     */
    protected $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('filestore.local');
    }

    /**
     * save file to given directory
     *
     * @param array $bucketConfig Directory name and region(unused in LOCAL)
     * @param array $filedetails  File details array
     *
     * @return file full path
     */
    public function save(array $bucketConfig, array $fileDetails)
    {
        $directory = $bucketConfig['name'];

        $content = file_get_contents($fileDetails['path']);

        $fileName = self::STORAGE_DIRECTORY . $directory . '/' . $fileDetails['key'];

        Storage::put($fileName, $content);

        return $this->getStorageDir() . $fileName;
    }

    public function delete(array $bucketConfig, $key)
    {
        $directory = $bucketConfig['name'];

        $fileName = self::STORAGE_DIRECTORY . $directory . '/' . $key;

        unlink($fileName); // nosemgrep : php.lang.security.unlink-use.unlink-use
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
