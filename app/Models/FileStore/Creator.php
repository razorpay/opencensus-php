<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
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

    /**
     * @var string content to be used for file
     */
    protected $content;

    /**
     * @var file entity to be created with given id
     */
    protected $id;

    /**
     * @var Store Handler
     */
    protected $storageHandler;

    const DEFAULT_STORE    = 's3';
    const DEFAULT_METADATA = [];

    public function __construct()
    {
        parent::__construct();

        $this->file = new Entity;

        $this->setDefaults();
    }

    public function setDefaults()
    {
        $this->store(self::DEFAULT_STORE);

        $this->file->setMetadata(self::DEFAULT_METADATA);
    }

    /**
     * Set the File name in File Store
     *
     * @param string $name File Name
     *
     * @return Creator object
     */
    public function name(string $name)
    {
        $this->file->setName($name);

        return $this;
    }

    /**
     * Set the Content of File
     *
     * @param string $content Content of file
     *
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
     * Set the Extension of File Store
     *
     * @param string $extension Extension of file
     *
     * @return Creator object
     */
    public function extension(string $extension)
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
     *
     * @param string $store Service to be used for storing file
     *
     * @return Creator object
     */
    public function store(string $store)
    {
        $this->file->setStore($store);

        $this->storageHandler = Store::getHandler($store);

        return $this;
    }

    /**
     * Set the type of File Store
     *
     * @param string $type File type
     *
     * @return Creator object
     */
    public function type(string $type)
    {
        $this->file->setType($type);

        return $this;
    }

    /**
     * Set the metadata of S3 file entity
     *
     * @param  string $metadata metadata value
     * @return Creater object
     */
    public function metadata(array $metadata)
    {
        $this->file->setMetadata($metadata);

        return $this;
    }

    /**
     * Set the id of File Store entity
     *
     * @param  string $id id value
     * @return Creater object
     */
    public function id(string $id)
    {
        $this->file->setId($id);

        return $this;
    }

    /**
     * Set the Entity of File Store
     * @return Creator object
     */
    public function entity(Base\Entity $entity)
    {
        $this->file->entity()->associate($entity);

        return $this;
    }

    /**
     * Set the delimiter used for creation of file
     *
     * @param string $delimiter Delimiter value
     *
     * @return Creator object
     */
    public function delimiter(string $delimiter = ',')
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
     *
     * @return Creator object
     */
    public function save()
    {
        $this->validateBeforeSave();

        if ($this->localFile === null)
        {
            $this->writeToLocalFile();
        }
        else
        {
            $this->filePath = $this->localFile->getPathName();
        }

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
     *
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
            'key'       => $fileName,
            'path'      => $this->filePath,
            'mime'      => $this->file->getMime(),
            'metadata'  => $this->file->getMetadata(),
        ];

        $location = $this->storageHandler->save($bucket, $fileDetails);

        $this->file->setLocation($fileDetails['key']);
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
            case Format::ENC:
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

        $dir = dirname($fullPath);

        if (file_exists($dir) === false)
        {
            mkdir($dir, 0777, true);
        }

        $file = fopen($fullPath, 'w');
        fwrite($file, $this->content);
        fclose($file);

        try
        {
            chmod($fullPath, 0777);  // keep it 0777. This step is important
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::WARNING,
                TraceCode::FILE_PERMISSION_CHANGE_FAILED,
                [
                    'path' => $fullPath
                ]);
        }

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
            $merchant = $this->merchant;
        }
        else
        {
            $type = $this->file->getType();

            Type::isTypeForSharedAccount($type);

            $merchant = $this->repo->merchant->getSharedAccount();
        }

        $this->file->merchant()->associate($merchant);
    }

    protected function getFullFilePath()
    {
        return $this->getStorageDir() . $this->file->getName() . '.' .$this->file->getExtension();
    }

    protected function getStorageDir()
    {
        return storage_path(Store::STORAGE_DIRECTORY);
    }
}
