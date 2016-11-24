<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Models\FileStore\Formatter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Creator extends Base\Core
{
    /**
     * @var Entity
     */
    protected $file;

    /**
     * Local file instance
     * @var UploadedFile
     */
    protected $localFile;

    /**
     * @var string delimiter used in file
     */
    protected $delimiter;

    /**
     * @var array column Formatter used in file
     */
    protected $columnFormat = [];
    /**
     * @var string file Path of local file
     */
    protected $filePath;

    protected $content;

    /**
     * @var Store Handler
     */
    protected $storageHandler;

    const DEFAULT_STORE = 's3';

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
        $this->store(self::DEFAULT_STORE);
    }

    /**
     * Set the File name in File Store
     * @return Creator object
     */
    public function name($name)
    {
        $this->file->setName($name);

        return $this;
    }

    /**
     * Set the Content of File
     * @return Creator object
     */
    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set the Local file
     * @return Creator object
     */
    public function localFile($file)
    {
        $this->localFile = $file;

        return $this;
    }

    /**
     * Set the Extention of File Store
     * @return Creator object
     */
    public function extension($extension)
    {
        $this->file->setExtension($extension);

        return $this;
    }

    /**
     * Set the Mime of File Store
     * @return Creator object
     */
    public function mime($mime)
    {
        $this->file->setMime($mime);

        return $this;
    }

    /**
     * Set the Store  of File Store
     * @return Creator object
     */
    public function store($store)
    {
        $this->file->setStore($store);

        $this->storageHandler = Store::getHandler($store);

        return $this;
    }

    /**
     * Set the type of File Store
     * @return Creator object
     */
    public function type($type)
    {
        $this->file->setType($type);

        return $this;
    }

    /**
     * Set the delimiter used for creation of file
     * @return Creator object
     */
    public function delimiter($delimiter = ',')
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * Set the Column Format used for creation of file
     * @return Creator object
     */
    public function columnFormat($columnFormat = [])
    {
        $this->columnFormat = $columnFormat;

        return $this;
    }

    /**
     * Creates a local file instance,
     * upload it to service specified and creates file store entity
     * @return Creator object
     */
    public function save()
    {
        $this->validateBeforeSave();

        if ($this->localFile === null)
        {
            $this->writeToLocalFile();
        }
        // TODO : add else condition and add filePath

        $this->mime($this->localFile->getMimeType());

        $this->validateBeforeUpload();

        $this->upload();

        $this->associateMerchantWithFile();

        $this->file->setSize(filesize($this->filePath));

        $this->repo->saveOrFail($this->file);

        return $this;
    }

    /**
     * Returns Array of File Store Values
     * @return array
     */
    public function get()
    {
        $data = $this->file->toArrayPublic();

        $data['local_file_path'] = $this->getFullFilePath();

        return $data;
    }

    protected function validateBeforeSave()
    {
        Format::validateContentTypeForExtension($this->content, $this->file->getExtension());

        Type::validateType($this->file->getType());
    }

    protected function validateBeforeUpload()
    {
        $extension = $this->file->getExtension();

        $mime = $this->file->getMime();

        Format::validateMimeForExtension($mime, $extension);
    }

    /**
     * Uploads the file to the service specified by file store
     *
     * @return void
     * @throws \Exception
     */
    protected function upload()
    {
        $bucket = $this->storageHandler->getBucketName($this->file->getType());

        $this->file->setBucket($bucket);

        $fileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileDetails = [
            'name'      => $fileName,
            'path'      => $this->filePath,
            'mime'      => $this->file->getMime(),
            'metadata'  => [],
        ];

        $location = $this->storageHandler->save($bucket, $fileDetails);

        $this->file->setLocation($location);
    }

    /**
     * Write the contents to a local file, for valid file extension
     *
     * @return void
     * @throws Exception\LogicException
     */
    protected function writeToLocalFile()
    {
        $extension = $this->file->getExtension();

        switch($extension)
        {
            case Format::TXT:
                $this->writeTextFile();

                break;

            case Format::XLSX:
                \Config::set('excel::export.calculate', true);

            case Format::CSV:
                $this->writeToExcelFile();

                break;

            case 'default':
                throw new Exception\LogicException('Not A Valid Extension');
        }
    }

    protected function writeTextFile()
    {
        $fileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fullPath = $this->getFullFilePath();

        if (file_exists($this->getStorageDir()) === false)
        {
            mkdir($this->getStorageDir(), 0777, true);
        }

        $file = fopen($fullPath, 'w');
        fwrite($file, $this->content);
        fclose($file);

        chmod($fullPath, 0777);  // keep it 0777. This step is important

        $this->createUploadedFile($fullPath, $fileName);
    }

    protected function writeToExcelFile()
    {
        $fileNameWithoutExt = $this->file->getName();

        $fileMetadata = Formatter\ExcelFormatter::writeToExcelFile(
            $this->content,
            $fileNameWithoutExt,
            $this->columnFormat,
            $this->file->getExtension(),
            self::DEFAULT_STORE);

        $this->createUploadedFile($fileMetadata['full'], $fileMetadata['file']);
    }

    protected function createUploadedFile($filePath, $fileName)
    {
        $file = new UploadedFile($filePath, $fileName);

        $this->localFile = $file;

        $this->filePath = $filePath;
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
        // TODO : Add logs
        $type = $this->file->getType();

        if (Type::isTypeForSharedAccount($type))
        {
            $this->file->setMerchantId(self::DEFAULT_MERCHANT_ID);
        }
    }

    protected function getRelativePath()
    {
        return self::STORAGE_DIRECTORY . $this->file->getName() . '.' .$this->file->getExtension();
    }

    protected function getFullFilePath()
    {
        return $this->getStorageDir() . $this->file->getName() . '.' .$this->file->getExtension();
    }

    protected function getStorageDir()
    {
        return storage_path(self::STORAGE_DIRECTORY);
    }
}
