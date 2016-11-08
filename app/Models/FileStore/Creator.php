<?php

namespace RZP\Models\FileStore;

use Storage;

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

    //TODO : Add comments for each variable
    protected $fileType;

    protected $type;

    protected $delimiter;

    protected $filePath;

    protected $storageHandler;

    protected $merchant;

    const DEFAULT_STORE = 's3';

    const DEFAULT_ENCRYPTION_METHOD = 'none';

    const DEFAULT_MERCHANT_ID = Account::SHARED_ACCOUNT;

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
        $this->file->encryption_method = self::DEFAULT_ENCRYPTION_METHOD;

        // TODO : Fix default Merhcant ID
        $this->file->merchant_id = self::DEFAULT_MERCHANT_ID;
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

    public function store($store)
    {
        $this->file->setStore($store);

        return $this;
    }

    public function type($type)
    {
        $this->file->setType($type);

        return $this;
    }

    public function delimiter($delimiter = ',')
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    protected function validateBeforeSave()
    {
        Format::validateContentTypeForFormat($this->content, $this->file->getFormat());

        Type::validateType($this->file->getType());
    }

    public function save()
    {
        $this->validateBeforeSave();

        if ($this->localFile === null)
        {
            $this->writeToLocalFile();
        }

        $this->upload();

        $relativePath = $this->getRelativePath($this->file->name);

        $merchantId = $this->file->getMerchantId();

        if ($merchantId === null)
        {
            $this->setDefaultMerchantId();
        }

        $this->file->size = Storage::size($relativePath);

        $this->repo->saveOrFail($this->file);

        return $this;
    }

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

    public function get()
    {
        return $this->file->toArrayPublic();
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
            throw new Exception\LogicException('Not A Valid Format');
        }
    }

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
        $path = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        return $path . self::STORAGE_DIRECTORY;
    }
}
