<?php

namespace RZP\Models\FileStore;

use Storage as LaravelStorage;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;

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

    /**
     * @var delimiter used in file
     */
    protected $delimiter;


    /**
     * @var file Path of local file
     */
    protected $filePath;

    protected $content;

    /**
     * @var Store Handler
     */
    protected $storageHandler;

    const DEFAULT_STORE = 's3';

    const DEFAULT_ENCRYPTION_METHOD = 'none';

    /**
     * Default Merchant ID, for File Type which are not part of any merchant
     */
    const DEFAULT_MERCHANT_ID = Account::SHARED_ACCOUNT;

    const STORAGE_DIRECTORY = 'files/filestore/';

    public function __construct()
    {
        parent::__construct();

        $this->file = new Entity;

        $this->setDefaults();
    }

    public function setDefaults()
    {
        $this->file->encryption_method = self::DEFAULT_ENCRYPTION_METHOD;
    }

    /**
     * Set the File name in File Store
     * @return FileStore\Creater object
     */
    public function name($name)
    {
        $this->file->name = $name;

        return $this;
    }

    /**
     * Set the Content of File
     * @return FileStore\Creater object
     */
    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set the Local file
     * @return FileStore\Creater object
     */
    public function localFile($file)
    {
        $this->localFile = $file;

        return $this;
    }

    /**
     * Set the Format of File Store
     * @return FileStore\Creater object
     */
    public function format($format)
    {
        $this->file->setformat($format);

        return $this;
    }

    /**
     * Set the Store  of File Store
     * @return FileStore\Creater object
     */
    public function store($store)
    {
        $this->file->setStore($store);

        return $this;
    }

    /**
     * Set the type of File Store
     * @return FileStore\Creater object
     */
    public function type($type)
    {
        $this->file->setType($type);

        return $this;
    }

    /**
     * Set the delimiter used for creation of file
     * @return FileStore\Creater object
     */
    public function delimiter($delimiter = ',')
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * Creates a local file instance,
     * upload it to service specified and creates file store entity
     * @return FileStore\Creater object
     */
    public function save()
    {
        $this->validateBeforeSave();

        if ($this->localFile === null)
        {
            $this->writeToLocalFile();
        }

        $this->upload();

        $fullPath = $this->getFullFilePath($this->file->name);

        $this->associateMerchantWithFile();

        $this->file->size = filesize($fullPath);

        $this->file->setSize(filesize($fullPath));

        $this->repo->saveOrFail($this->file);

        return $this;
    }

    /**
     * Returns Array of File Store Values
     * @return array
     */
    public function get()
    {
        return $this->file->toArrayPublic();
    }

    protected function validateBeforeSave()
    {
        Format::validateContentTypeForFormat($this->content, $this->file->getFormat());

        Type::validateType($this->file->getType());
    }

    /**
     * Uploads the file to the service specified by file store
     * @return void
     * @throws \Exception
     */
    protected function upload()
    {
        if ($this->file->getStore() === null)
        {
            $this->file->setStore(self::DEFAULT_STORE);
        }

        $this->storageHandler = Store::getHandler($this->file->getStore());

        $bucket = $this->storageHandler->getBucketName($this->file->type);

        $this->file->location = $this->storageHandler->save(
            $bucket,
            $this->file->name,
            $this->filePath,
            $this->file->getformat(),
            []
        );
    }

    /**
     * Write the contents to a local file, fo valid file formats
     *
     * @return void
     * @throws Exception\LogicException
     */
    protected function writeToLocalFile()
    {
        if ($this->file->getFormat() === Format::TXT)
        {
            $content = $this->content;

            $fullPath = $this->getFullFilePath($this->file->name);

            $file = fopen($fullPath, 'w');
            fwrite($file, $content);
            fclose($file);

            chmod($fullPath, 0777);  // keep it 0777. This step is important.

            $this->filePath = $fullPath;
        }
        else
        {
            throw new Exception\LogicException('Not A Valid Format');
        }
    }

    protected function associateMerchantWithFile()
    {
        if ($this->merchant !== null)
        {
            $this->file->merchant()->associate($this->merchant);
        }
        else
        {
            $this->setDefaultMerchantId();
        }
    }

    /**
     * Sets the Merchant id for file store to shared account's merchant id
     *
     * @return void
     */
    protected function setDefaultMerchantId()
    {
        $type = $this->file->getType();

        if (Type::isTypeForSharedAccount($type))
        {
            $this->file->setMerchantId(self::DEFAULT_MERCHANT_ID);
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
        return $path = storage_path(self::STORAGE_DIRECTORY);
    }
}
