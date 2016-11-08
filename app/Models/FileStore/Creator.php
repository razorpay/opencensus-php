<?php

namespace RZP\Models\FileStore;

use Storage;

use RZP\Exception;
use RZP\Models\Base;

class Creator extends Base\Core
{
    /**
     * @var FileStore\Entity
     */
    protected $file;

    /**
     * Local file instance
     * @var UploadedFile
     */
    protected $localFile;

    //TODO : Add comments for each variable
    protected $fileType;

    protected $type;

    protected $delimiter;

    protected $filePath;

    protected $storageHandler;

    protected $serviceProvider;

    const DEFAULT_SERVICE_PROVIDER = 's3';

    const STORAGE_DIRECTORY = 'files/file_handler/';

    public function __construct()
    {
        parent::__construct();

        $this->file = new Entity;

        $this->setDefaults();
    }

    public function name($name)
    {
        $this->file->name = $name;

        return $this;
    }

    public function setDefaults()
    {
        $this->file->encryption_method = 'none';

        $this->file->merchant_id = '10000000000000';
    }

    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    public function localFile($file)
    {
        $this->localFile = $file;

        return $this;
    }

    public function format($format)
    {
        $this->file->setformat($format);

        return $this;
    }

    public function delimiter($delimiter = ',')
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function save()
    {
        $result = Format::validateContentTypeForFormat($this->content, $this->file->getFormat());

        if ($result === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Content type not valid for file format specified.');
        }

        if ($this->localFile === null)
        {
            $this->writeToLocalFile();
        }

        $this->upload();

        $this->file->type = 'abc';

        $relativePath = $this->getRelativePath($this->file->name);

        $this->file->size = Storage::size($relativePath);

        $this->repo->saveOrFail($this->file);

        return $this;
    }

    protected function upload()
    {
        $this->file->service = self::DEFAULT_SERVICE_PROVIDER;

        $this->getStorageHandle($this->file->service);

        $this->file->location = $this->storageHandler->save(
            'default_bucket', // TODO : fix bucket
            $this->file->name,
            $this->filePath,
            $this->file->getformat(),
            []
        );
    }

    public function get()
    {
        return $this->file->toArrayPublic();
    }

    protected function getStorageHandle($service)
    {
        $class = 'RZP\Models\FileStore\Storage\\';

        if ($this->serviceProvider !== $service)
        {
            switch ($service)
            {
                // TODO : change to constants
                case 's3':
                    $class  = $class . 'AwsS3' . '\Handler';
                    break;

                case 'default':
                    throw new Exception\InvalidArgumentException(
                        'Invalid storage service ' . $service);
            }

            $this->storageHandler = (new $class);

            $this->serviceProvider = $service;
        }
    }

    protected function writeToLocalFile()
    {
        if ($this->file->getFormat() === Format::TXT)
        {
            $content = $this->content;

            $fullPath = $this->getFullFilePath($this->file->name);

            $relativePath = $this->getRelativePath($this->file->name);

            Storage::put($relativePath, $content);

            chmod($fullPath, 0777);

            $this->filePath = $fullPath;
        }
        else
        {
            //TODO : Throw proper error
            throw new Exception\BadRequestValidationFailureException(
                'Local file not found');
        }
    }

    protected function getRelativePath()
    {
        return self::STORAGE_DIRECTORY . $this->file->name;
    }

    protected function getFullFilePath()
    {
        return $this->getStorageDir() . $this->file->name;
    }

    protected function getStorageDir()
    {
        $path = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        return $path . self::STORAGE_DIRECTORY;
    }
}
