<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Encryption;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Core;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FileStore\Formatter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Facades\Config;

class Creator extends Base\Core
{
    /**
     * Entity Instance
     *
     * @var Entity
     */
    protected $file;

    /**
     * Local file instance
     *
     * @var UploadedFile
     */
    protected $localFile;

    /**
     * Local file path
     *
     * @var string localFilePath
     */
    protected $localFilePath;

    /**
     * Delimiter used in file
     *
     * @var string delimiter
     */
    protected $delimiter;

    /**
     * Column Formatter used in file
     *
     * @var array columnFormat
     */
    protected $columnFormat = [];

    /**
     * Headers should be presnt/absent in excel/csv file
     *
     * @var bool headers
     */
    protected $headers = true;

    /**
     * File Path of Local File
     *
     * @var string filePath
     */
    protected $filePath;

    /**
     * Content of File
     *
     * @var string content
     */
    protected $content;

    /**
     * Pre-assigned Id of entity
     *
     * @var string file entity to be created with given id
     */
    protected $id;

    /**
     * Storage Handler instance
     *
     * @var Store Handler
     */
    protected $storageHandler;

    /**
     * Store the environment value
     *
     * @var string Environment
     */
    protected $env;

    /**
     * Flag to signify if file has to be compressed
     *
     * @var boolean Compress flag
     */
    protected $shouldCompress = false;

     /**
     * Flag to signify if file has to be encrypted
     *
     * @var boolean Encrypt flag
     */
    protected $shouldEncrypt = false;

    /**
     * Flag to signify if file has to be base64 encoded
     *
     * @var boolean Encode flag
     */
    protected $shouldEncode = false;

     /**
     * Encryption Handler Instance
     *
     * @var Encryption Handler
     */
    protected $encryptionHandler;

    /**
     * Format in which file has to be Compress
     *
     * @var string Compression Format
     */
    protected $compressionFormat;

    /**
     * Command to be used for zipping
     *
     * @var string Compression Command
     */
    protected $compressionCommand = null;


    /**
     * Flag to signify if local file needs to be deleted
     *
     * @var bool Flag
     */
    protected $shouldDeleteLocalFile = false;


    /**
     * String to signify to use new S3 Bucket for External Services
     * @var null
     */
    protected $s3BucketConfigForExternalServices = null;

    /**
     * The sheet name used when creating an excel file.
     * Sheet 1 is the default name used to generate the excel sheet.
     *
     * @var string
     */

    /**
     * s3 related additional parameters specific to s3 object.
     * key should be exactly same as s3 putobject params.
     * @var null
     */
    protected $additionalParameters = null;

    protected $sheetName = 'Sheet 1';

    const DEFAULT_STORE    = 's3';

    const COMMAND_FOR_ZIPPING = 'zip --junk-paths --move';

    public function init()
    {
        $this->file = new Entity;

        $this->file->generate([]);

        $this->setDefaults();
    }

    /**
     * Set the default Value for store and metadata
     */
    public function setDefaults()
    {
        $this->store(self::DEFAULT_STORE);
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
     * Set the name of the sheet of the excel file to be created.
     *
     * @param string $sheetName
     * @return $this
     */
    public function sheetName(string $sheetName)
    {
        $this->sheetName = $sheetName;

        return $this;
    }

    /**
     * Set the Content of File
     *
     * @param mixed $content Content of file
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
     *
     * @param UploadedFile $file Local File Instance
     *
     * @return Creator object
     */
    public function localFile($file)
    {
        $this->localFile = $file;

        return $this;
    }

    /**
     * Set the Local file Path
     *
     * @param string $filePath Local File Path
     *
     * @return Creator object
     */
    public function localFilePath(string $filePath)
    {
        $this->localFilePath = $filePath;

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
     *
     * @param string $mime Mime
     *
     * @return Creator object
     */
    public function mime($mime)
    {
        $this->file->setMime($mime);

        return $this;
    }

    /** Set Compression Params for file
     *
     * @param Compression format
     *
     * @return Creator object
     */
    public function compress($format = 'zip')
    {
        $this->shouldCompress = true;

        $this->compressionFormat = $format;

        $this->setCompressionCommand();

        return $this;
    }

    /** Encrypts contents of file
     *
     * @param string $type  type of encryption
     * @param string $secret secret for encryption
     *
     * @return Creator object
     */
    public function encrypt(string $type, array $params)
    {
        $this->shouldEncrypt = true;

        $this->encryptionHandler = new Encryption\Handler($type, $params);

        return $this;
    }

    /** Encodes the given file with base64
     * @return $this
     */
    public function encode()
    {
        $this->shouldEncode = true;

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
     * Set the Password of File Store
     *
     * @param string $password Password
     *
     * @return Creator object
     */
    public function password(string $password)
    {
        $this->file->setPassword($password);

        return $this;
    }

    /**
     * Set the metadata of S3 file entity
     *
     * @param array $metadata metadata value
     *
     * @return Creator
     */
    public function metadata(array $metadata)
    {
        $this->file->setMetadata($metadata);

        return $this;
    }

    /**
     * Set the header flag for excel/csv files
     *
     * @param bool $header headers value
     *
     * @return Creator
     */
    public function headers(bool $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Set the id of File Store entity
     *
     * @param string $id id value
     *
     * @return Creator object
     */
    public function id(string $id)
    {
        $this->file->setId($id);

        return $this;
    }

    /**
     * Set the Entity of File Store
     *
     * @param Base\Entity $entity entity object
     *
     * @return Creator object
     */
    public function entity(Base\Entity $entity)
    {
        $this->file->entity()->associate($entity);

        if ($entity->hasRelation('merchant'))
        {
            $this->merchant = $entity->merchant;
        }

        return $this;
    }

    public function additionalParameters(array $additionalParameters)
    {
        if (empty($additionalParameters) === false)
        {
            $this->additionalParameters = $additionalParameters;
        }

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
     *
     * @param array $columnFormat format of column
     *
     * @return Creator object
     */
    public function columnFormat($columnFormat = [])
    {
        $this->columnFormat = $columnFormat;

        return $this;
    }

    /**
     * Sets merchant which is used for association later.
     *
     * @param Merchant\Entity $merchant Merchant Entity
     *
     * @return Creator
     */
    public function merchant(Merchant\Entity $merchant)
    {
        $this->merchant = $merchant;

        return $this;
    }

    /**
     * Local file will be deleted after upload
     *
     * @return Creator
     */
    public function deleteLocalFile()
    {
        $this->shouldDeleteLocalFile = true;

        return $this;
    }

    /**
     * Add to S3 Bucket Configs for Batch Service MicroService Upload.
     *
     * @param string $bucketConfig
     *
     */
    public function addBucketConfigForBatchService(string $bucketConfig)
    {
        $this->s3BucketConfigForExternalServices = $bucketConfig;
    }

    /**
     * Creates a local file instance,
     * upload it to service specified and creates file store entity
     *
     * @return Creator object
     * @throws Exception\LogicException
     */
    public function save()
    {
        $this->validateBeforeSave();

        if ($this->localFile !== null)
        {
            $this->filePath = $this->localFile->getPathname();

            // Files generated for certain banking integrations during upload undergoes mime type validation.
            // When mime type is not set then the library tries to guess the mime type.
            // This produces type of incompatible nature and causes file upload level issues.
            // Hence, the change.
            if ($this->file->getMime() === null)
            {
                $this->mime($this->localFile->getMimeType());
            }
        }
        else if ($this->localFilePath !== null)
        {
            $this->filePath = $this->localFilePath;

            if ($this->file->getMime() === null)
            {
                throw new Exception\LogicException('Mime Type is not Set');
            }
        }
        else
        {
            $this->writeToLocalFile();

            if ($this->file->getMime() === null)
            {
                $this->mime($this->localFile->getMimeType());
            }
        }

        $this->validateBeforeUpload();

        $this->upload();

        $this->associateMerchantToFile();

        $this->file->setSize(filesize($this->filePath));

        $this->repo->saveOrFail($this->file);

        $this->deleteLocalFileIfRequired();

        return $this;
    }

    protected function deleteLocalFileIfRequired()
    {
        $filePath = $this->getFullFilePath();

        if (($this->shouldDeleteLocalFile === true) and
            (file_exists($filePath) === false))
        {
            unlink($filePath); // nosemgrep : php.lang.security.unlink-use.unlink-use
        }
    }

    /**
     * Returns Array of File Store Values
     *
     * @return array
     */
    public function get(): array
    {
        $data = $this->file->toArrayPublic();

        $data['local_file_path'] = $this->getFullFilePath();

        return $data;
    }

    /**
     * Returns signed url and id of File Entity
     *
     * @return array
     */
    public function getSignedUrl($duration = '15')
    {
        $bucketConfig = $this->storageHandler->getBucketConfig(
            $this->file->getType(),
            $this->env,
            $this->s3BucketConfigForExternalServices);

        $url = $this->storageHandler->getSignedUrl($bucketConfig, $this->file->getLocation(), $duration);

        // For Testing this will return Local File Path instead of Signed Url
        // TODO: Do this in a better way
        if ($this->env === 'testing')
        {
            $url = $this->getFullFilePath();
        }

        return [
            'id'  => $this->file->getId(),
            'url' => $url,
        ];
    }

    /**
     * Returns unsigned url
     *
     * @return string
     */
    public function getUrl(): string
    {
        $bucketConfig = $this->storageHandler->getBucketConfig(
            $this->file->getType(), $this->env);

        return $this->storageHandler->getUrl($bucketConfig, $this->file->location);
    }

    /**
     * Returns bucket config of the file
     *
     */
    public function getBucketConfig(string $fileType): array
    {
        $bucketConfig = $this->storageHandler->getBucketConfig(
            $fileType, $this->env);

        return $bucketConfig;
    }

    /**
     * Returns instance of FileStore Entity
     * TODO : think of a better way
     *
     * @return $this->file
     */
    public function getFileInstance()
    {
        return $this->file;
    }

    protected function setCompressionCommand()
    {
        switch ($this->compressionFormat)
        {
            case 'zip':
                $this->compressionCommand = self::COMMAND_FOR_ZIPPING;
                break;

            default:
                throw new Exception\LogicException('Not A Valid Compression Format ' . $this->compressionFormat);
        }
    }

    /**
     * Validates the Content before saving
     */
    protected function validateBeforeSave()
    {
        Format::validateContentTypeForExtension($this->content, $this->file->getExtension());

        Type::validateType($this->file->getType());

        $this->validateCompressionSupport();
    }

    protected function validateCompressionSupport()
    {
        if (($this->shouldCompress === true) and
            (($this->localFile !== null) or
            ($this->localFilePath !== null)))
        {
            throw new Exception\LogicException(
                'Compression is not supported for Local Files');
        }
    }

    /**
     * Validates the Mime and Extension before uploading
     */
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
        $bucketConfig = $this->storageHandler->getBucketConfig(
            $this->file->getType(),
            $this->env,
            $this->s3BucketConfigForExternalServices);

        $this->updateBucketConfigForCommissionInvoice($bucketConfig);

        $fileName = $this->getFullFileName();

        $fileDetails = [
            'key'       => $fileName,
            'path'      => $this->filePath,
            'mime'      => $this->file->getMime(),
            'metadata'  => $this->file->getMetadata(),
        ];

        if(substr($fileName,0,12) === 'Rbl_Emi_File')
        {
            $fileDetails['key'] = 'rbl-emi/' . $fileName;
            $bucketConfig['name'] = Config::get('applications.chota_beam.bucket_name');
        }

        if (empty($this->additionalParameters) === false)
        {
            $fileDetails['additional_parameters'] = $this->additionalParameters;
        }

        $location = $this->storageHandler->save($bucketConfig, $fileDetails);

        $this->file->setLocation($fileDetails['key']);

        $this->trace->info(
            TraceCode::DEBUG_LOGGING,
            [
                'bucketname for s3' => $bucketConfig,
            ]
        );

        $this->file->setBucket($bucketConfig['name']);

        $this->file->setRegion($bucketConfig['region']);
    }

    protected function updateBucketConfigForCommissionInvoice(&$bucketConfig)
    {
        if($this->file->getType() === Type::COMMISSION_INVOICE)
        {
            $merchantId = $this->merchant->getId();

            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $this->app['config']->get('app.commission_invoice_bucket_migration_exp_id')
            ];

            $isExpEnable = (new Core())->isSplitzExperimentEnable($properties,'enable');

            $this->trace->info(TraceCode::BUCKET_MIGRATION_FOR_NEW_COMMISSION_INVOICES_EXP,[
                'merchant_id' => $merchantId,
                'isExpEnable' => $isExpEnable,
            ]);

            if($isExpEnable === true)
            {
                $configType = Type::COMMISSION_INVOICE_AP_SOUTH_BUCKET_CONFIG;

                $config = $this->app['config']->get('filestore.aws');

                $bucketConfig = $config[$configType];
            }
        }

        return $bucketConfig;
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

        $this->createDirectory();

        switch($extension)
        {
            case Format::TXT:
            case Format::IN:
            case Format::ENC:
            case Format::PDF:
            case Format::DAT:
            // When the extension of the file which need to be created has no standard extension (in case of ASCII file)
            // we dont set the extension while creating it, then `NONE` will match with it and process it as text file
            case Format::NONE:
            case Format::VAL:
                $this->writeTextFile();
                break;

            case Format::XLS:
            case Format::XLSX:
            case Format::CSV:
                $this->writeToExcelFile();

                break;

            default:
                throw new Exception\LogicException('Not A Valid Extension');
        }

        if ($this->shouldEncrypt === true)
        {
            $this->encryptFile();
        }

        if ($this->shouldCompress === true)
        {
            $this->compressFile();
        }

        if ($this->shouldEncode === true)
        {
            $this->encodeFile();
        }

        $this->updateFilePermission();
    }

    protected function encryptFile()
    {
        $fileToBeEncrypted = $this->getFullFilePath();

        $this->encryptionHandler->encryptFile($fileToBeEncrypted);
    }

    protected function encodeFile()
    {
        $fileToBeEncoded = $this->getFullFilePath();

        $data = file_get_contents($fileToBeEncoded);

        $encodedData = base64_encode($data);

        file_put_contents($fileToBeEncoded, $encodedData);
    }

    /*
     * Compress the file and stores in the Compressed file Path
     * Sample Command :
     * `zip --junk-paths --move  --password <password> '<compression_path>' '<source_path>'`
     */
    protected function compressFile()
    {
        $unzippedFilePath = $this->getFullFilePath();

        $compressionCommand = $this->compressionCommand;

        if (empty($this->file->getPassword()) === false)
        {
            $compressionCommand .= " --password " . $this->file->getPassword();
        }

        exec( // nosemgrep : php.lang.security.exec-use.exec-use
            escapeshellcmd($compressionCommand) .
            " " .
            escapeshellarg($this->getCompressedFileFullPath()) .
            " " .
            escapeshellarg($unzippedFilePath)
        );

        $this->extension($this->compressionFormat);

        $this->createUploadedFile($this->getFullFilePath(), $this->getFullFileName());

        $this->mime($this->localFile->getMimeType());
    }

    protected function createDirectory()
    {
        $fullPath = $this->getFullFilePath();

        $dir = dirname($fullPath);

        if (file_exists($dir) === false)
        {
            (new Utility)->callFileOperation('mkdir', [$dir, 0777, true]);
        }
    }

    protected function writeTextFile()
    {
        $fileName = $this->getFullFileName();

        $fullPath = $this->getFullFilePath();

        $file = fopen($fullPath, 'w');
        fwrite($file, $this->content);
        fclose($file);

        $this->createUploadedFile($fullPath, $fileName);
    }

    protected function writeToExcelFile()
    {
        $fileNameWithoutExt = $this->file->getName();

        $fileMetadata = Formatter\ExcelFormatter::writeToExcelFile(
                                                        $this->content,
                                                        $fileNameWithoutExt,
                                                        $this->columnFormat,
                                                        $this->headers,
                                                        $this->file->getExtension(),
                                                        $this->getStorageDir(),
                                                        $this->sheetName);

        $this->createUploadedFile($fileMetadata['full'], $fileMetadata['file']);
    }

    /**
     * Updates permission to 777 on any local files generated via filestore, so that
     * delete operations can be performed successfully on them
     */
    protected function updateFilePermission()
    {
        try
        {
            //
            // This step is important because file can be created
            // via different users (www-data or ubuntu (via queue))
            //
            $filePermission = substr(sprintf('%o', fileperms($this->filePath)), -3);

            if ($filePermission !== '777')
            {
                (new Utility)->callFileOperation('chmod', [$this->filePath, 0777]);

                $this->trace->debug(
                    TraceCode::CHANGING_FILE_PERMISSION,
                    [
                        'file_path'                 => $this->filePath,
                        'current_file_permission'   => $filePermission,
                    ]);
            }
        }
        catch (\Throwable $e)
        {
             $this->trace->traceException(
                 $e,
                 Trace::WARNING,
                 TraceCode::FILE_PERMISSION_CHANGE_FAILED,
                 [
                     'path' => $this->filePath
                 ]);
        }
    }

    protected function createUploadedFile($filePath, $fileName)
    {
        $file = new UploadedFile($filePath, $fileName);

        $this->localFile = $file;

        $this->filePath = $filePath;
    }

    protected function associateMerchantToFile()
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

    public function getFullFileName()
    {
        $extension = $this->file->getExtension();

        $fileName  = $this->file->getName();

        if ($extension !== null)
        {
            $fileName .= ('.' . $extension);
        }

        return $fileName;
    }

    public function getFullFilePath()
    {
        return $this->getStorageDir() . $this->getFullFileName();
    }

    public function getCompressedFileFullPath()
    {
        return $this->getStorageDir() . $this->file->getName() . '.' . Format::ZIP;
    }

    protected function getStorageDir()
    {
        return storage_path(Store::STORAGE_DIRECTORY);
    }
}
