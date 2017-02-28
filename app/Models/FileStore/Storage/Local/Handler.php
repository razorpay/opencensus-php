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
     * @param string $directory   directory name
     * @param array  $filedetails file details array
     *
     * @return file full path
     */
    public function save($directory, $fileDetails)
    {
        $content = file_get_contents($fileDetails['path']);

        $fileName = self::STORAGE_DIRECTORY . $directory . '/' . $fileDetails['key'];

        Storage::put($fileName, $content);

        return $this->getStorageDir() . $fileName;
    }

    /**
     * Save File as(Not Implemented for local)
     *
     * @param string $bucket   bucket name
     * @param string $key      key name
     * @param string $filePath file path
     *
     * @return void
     */
    public function saveAs($bucket, $key, $filePath)
    {
        ;
    }

    /**
     * Get Full Storage Directory
     *
     * @return string full storage dircetory
     */
    protected function getStorageDir()
    {
        $path = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        return $path;
    }
}
