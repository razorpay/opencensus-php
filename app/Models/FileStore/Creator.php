<?php

namespace RZP\Models\FileStore;

use Storage;

use RZP\Exception;
use RZP\Models\Base;

use Symfony\Component\HttpFoundation\File\File;

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

        $this->file->size = $this->getFileSize();

        $this->repo->saveOrFail($this->file);

        return $this;
    }

    protected function getFileSize()
    {
        $file = new File($this->filePath);

        return $file->getSize();
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
        $class = 'RZP\Models\FileStore\StorageService\\';

        if ($this->serviceProvider !== $service)
        {
            switch ($service)
            {
                // TODO : change to constants
                case 's3':
                    $class  = $class . 'AwsS3' . '\Handler';
                    break;

                case 'default':
                    throw new Exception\InvalidArgumentException('Invalid storage service ' . $service);
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

            $fullpath = $this->getFullFilePath($this->file->name);

            file_put_contents($fullpath, $content);

            chmod($fullpath, 0777);  // keep it 0777. This step is important.

            $this->filePath = $fullpath;
        }
        else
        {
            //TODO : Throw proper error
            throw new Exception\BadRequestValidationFailureException(
                'Local file not found');
        }
    }

    protected function getFullFilePath()
    {
        $filePath = $this->getStorageDir();

        return $filePath . $this->file->name;
    }

    protected function getStorageDir()
    {
        $path = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        $storageDirectory = 'files/file_handler/';

        return $path . $storageDirectory;
    }
}
