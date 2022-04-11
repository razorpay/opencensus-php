<?php

namespace RZP\Models\Lambda;

use File;
use Request;
use RZP\Base\RuntimeManager;
use RZP\Exception;
use RZP\Mail\Base\Constants;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Gateway\File\Constants as GatewayConstants;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Services\UfhService;
use RZP\Models\Merchant\Document;
use RZP\Models\FundTransfer\Kotak;
use RZP\Reconciliator\FileProcessor;
use Symfony\Component\HttpFoundation;
use RZP\Models\Merchant\Document\Entity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class Service extends Base\Service
{
    use Kotak\FileHandlerTrait;

    const MUTEX_RESOURCE = 'LAMBDA_REQUEST_%s';

    // 30 minutes
    const MUTEX_LOCK_TIMEOUT = 1800;

    protected $fileProcessor = null;

    protected $ufh;

    protected $mutex;

    const SFTP_BUCKET_TARGETS = [
        Batch\Constants::ENACH_NB_ICICI,
        Batch\Constants::ENACH_RBL,
    ];

    // skips extracting the zip file before creating the batch
    // the extraction is handled as part of batch processing
    const SKIP_INPUT_EXTRACT = [
        Batch\Type::NACH
    ];

    const RBL = "rbl";
    const ICICI = "icici";
    
    protected static $headers = [
        'MID',
        'Merchant name',
        'Partner_Name',
        'Partner_Code',
        'TID',
        'TID Creation Date',
        'Date of Onboarding',
        'Legal Name',
        'DBA Name',
        'Machine Type',
        'Terminal Model',
        'Address1',
        'State',
        'City',
        'Location',
        'PIN Code',
        'Phone No. (Landline)',
        'Mobile No.',
        'Email Id',
        'Contact Person Name',
        'Merchant Category Code',
        'MCC Description',
        'Beneficiary Account NAME',
        'Beneficiary Account No',
        'Beneficiary Address',
        'IFSC Code',
        'Payment Mode',
        'Activate Date',
        'Current Status',
        'Business Website',
        'Purpose Code',
        'Purpose Code Description',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->fileProcessor = new FileProcessor;

        $this->mutex = $this->app['api.mutex'];

        $this->merchant = $this->repo->merchant->getSharedAccount();

        $this->ufh = (new UfhService($this->app));
    }

    public function processLambda(string $type, array $input)
    {
        $this->trace->info(
            TraceCode::LAMBDA_REQUEST,
            [
                'type'    => $type,
                'input'   => $input
            ]);

        $input['type'] = $type;

        $batches = $this->mutex->acquireAndRelease(
            sprintf(self::MUTEX_RESOURCE, strtoupper($type)),
            function () use ($input, $type)
            {
                list($file, $locationType) = $this->getFileDetails($input, $type);

                return $this->createBatches($input, $file, $locationType);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_LAMBDA_ANOTHER_OPERATION_IN_PROGRESS,
            5,
            2000,
            4000);

        return $batches->toArrayPublic();
    }

    protected function getFileDetails(array & $input, string $type,bool $configKey = false)
    {
        if (isset($input['key']) === true)
        {
            // TODO: add validation for key
            $key = urldecode($input['key']);

            $target = $input['gateway'] ?? null;

            if (($type === Batch\Type::NACH) or
                (in_array($target, self::SFTP_BUCKET_TARGETS, true)))
            {
                $filePath = $this->getH2HFileFromAws($key, true, 'sftp_bucket', 'ap-south-1',$configKey);
            }
            else
            {

                // Adding this to migrate the lambdas to indian region bucket
                // with old lambda bucket and region was not being passed
                // thus added this step to pass the bucket and region along with the new lambda
                // keeping the following config in order to support both the lmbdas old and new
                // to ease the migration process

                $bucketConfig = 'h2h_bucket';
                $bucketRegion = null;

                if(empty($input['bucket']) === false)
                {
                    $bucketConfig = $input['bucket'];
                    // doing this because the batch file creation will require this to be unset
                    // else in validation it will fail
                    unset($input['bucket']);
                }

                if (empty($input['region']) === false)
                {
                    $bucketRegion = $input['region'];
                    // doing this because the batch file creation will require this to be unset
                    // else in validation it will fail
                    unset($input['region']);
                }

                $filePath = $this->getH2HFileFromAws($key, true, $bucketConfig, $bucketRegion,$configKey);
            }

            $file = new HttpFoundation\File\File($filePath);

            $locationType = FileProcessor::STORAGE;

            unset($input['key']);
        }
        else if (isset($input['file']) === true)
        {
            $file = $input['file'];

            $locationType = FileProcessor::UPLOADED;

            unset($input['file']);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'invalid input, either bucket key or uploaded file is required');
        }

        return [$file, $locationType];
    }

    protected function createBatches($input, $file, $locationType)
    {
        $batches = new Base\PublicCollection;

        $files = [];

        if (($this->fileProcessor->isZipFile($file, $locationType) === true) and
            ((in_array($input['type'], self::SKIP_INPUT_EXTRACT, true) === false) and
            (($input['sub_type'] !== Batch\Constants::CANCEL) or ($input['gateway'] !== Batch\Constants::ENACH_RBL))))
        {
            // Gets the actual zip file's details first.
            $zipFileDetails = $this->fileProcessor->getFileDetails($file, $locationType);

            // Gets all files details present in the zip file.
            $files = $this->getFileDetailsFromZipFile($zipFileDetails);
        }
        else
        {
            $files[] = $file;
        }

        foreach ($files as $file)
        {
            $fileDetails = $this->fileProcessor->getFileDetails($file, $locationType, false);

            $this->trace->info(TraceCode::LAMBDA_FILE_DETAILS, $fileDetails);

            //
            // `file` laravel validation rule which is being used
            // in batch validator expects either File or UploadedFile type
            //
            if ((($file instanceof HttpFoundation\File\File) === false) and
                (($file instanceof HttpFoundation\File\UploadedFile) === false))
            {
                $file = new HttpFoundation\File\File($fileDetails[FileProcessor::FILE_PATH]);
            }

            $input['file'] = $file;

            try
            {
                $batch = (new Batch\Core)->create($input, $this->merchant);

                $batches->push($batch);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::LAMBDA_BATCH_FAILURE,
                    [
                        'file_details' => $file
                    ]);
            }
        }

        return $batches;
    }

    protected function getFileDetailsFromZipFile(array $zipFileDetails)
    {
        // unzipFile unzips the file and stores it in a location.
        $unzippedFolderPath = $this->fileProcessor->unzipFile($zipFileDetails);

        return File::allFiles($unzippedFolderPath);
    }

    public function processLambdaFIRS(array $input)
    {
        $this->trace->info(TraceCode::LAMBDA_REQUEST,
            [
                'input'   => $input
            ]);

        $configKey = true;

        list($file, $locationType) = $this->getFileDetails($input,'FIRS',$configKey);

        $fileDetails = $this->fileProcessor->getFileDetails($file, $locationType, false);

        $file = new HttpFoundation\File\UploadedFile($fileDetails['file_path'],$this->fileProcessor->getFileName($file),$fileDetails['mime_type'],$fileDetails['size'],null,true);

        $document = $this->uploadFileAndSaveInMerchantDocument($file,$input);

        $documentMetaData = [
            Entity::ID => $document->getId(),
            Entity::FILE_STORE_ID => $document->getFileStoreId(),
            Entity::MERCHANT_ID => $document->getMerchantId(),
        ];

        return $documentMetaData;
    }

    protected function uploadFileAndSaveInMerchantDocument(HttpFoundation\File\UploadedFile $file, array $input)
    {
        $filename = $file->getClientOriginalName();

        $ufhService = $this->app['ufh.service'];

       if($input['gateway'] === self::RBL)
       {
            list($company, $gatewayMerchantId, $date) = explode('_',$filename);

            $part = str_split($date,2);

            $terminal = $this->repo->terminal->findMerchantIdByGatewayMerchantIDAll($gatewayMerchantId);
            $merchantId = $terminal->getMerchantId();
            $merchant = $this->repo->merchant->find($merchantId);
            $storageFileName = 'FIRS/'.$merchantId.'/'.$part[1].'/'.$part[0].'/'.$filename;
            $type = 'firs_file';
            $documentDate = strtotime($part[0].'/'.date('d').'/'.$part[1]);

            $response = $ufhService->uploadFileAndGetResponse($file, $storageFileName, $type, $merchant);

            $this->trace->info(TraceCode::UPLOAD_FILE_DETAILS,
                [
                    'success'           => isset($response[GatewayConstants::ID]),
                ]);

            /*    
             * Commenting it out, because for now we are removing zip file generation logic
             * for RBL Files.
             * 
             * $this->deleteExistingZipFile($merchantId,$part);
            */

            $document = (new Document\Core)->saveInMerchantDocument($response,$merchantId,$type,$documentDate);
       }

       if($input['gateway'] === self::ICICI)
       {
            list($tag, $referenceNumber, $utrNumberAndFileExtension) = explode('_',$filename);
                
            $utrNumberAndFileExtension = ltrim($utrNumberAndFileExtension);
            $utrNumberAndFileExtension = rtrim($utrNumberAndFileExtension);
            list($utrNumber, $fileExtension) = explode('.',$utrNumberAndFileExtension);

            $settlement =  $this->repo->settlement->findSettlementByUTR($utrNumber);
            $merchantId = $settlement->getMerchantId();
            $merchant = $this->repo->merchant->find($merchantId);

            $firs_date = date('m/d/Y', $settlement->getUpdatedAt());

            list($month,$date,$year) = explode('/',$firs_date);
            
            $storageFileName = 'FIRS/'.$merchantId.'/'.$year.'/'.$month.'/'.$filename;
            $type = 'firs_icici_file';
            $documentDate = strtotime($month.'/'.'01'.'/'.$year);

            $response = $ufhService->uploadFileAndGetResponse($file, $storageFileName, $type, $merchant);

            $this->trace->info(TraceCode::UPLOAD_FILE_DETAILS,
                [
                    'success'           => isset($response[GatewayConstants::ID]),
                ]);

            $document = (new Document\Core)->saveInMerchantDocument($response,$merchantId,$type,$documentDate);
       }

        return $document;
    }

    protected function deleteExistingZipFile(string $merchantId, array $part)
    {
        $from = strtotime($part[0].'/01/'.$part[1]);
        $to = strtotime("+1 Month",$from);

        $ufhService = $this->app['ufh.service'];

        $documentEntities = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypeAndDate($merchantId,'firs_zip',$from,$to);

        foreach($documentEntities as $documentEntity)
        {
            if ($documentEntity != null)
            {
                $ufhService->deleteFile($documentEntity->getPublicFileStoreId(),$merchantId,'firs_zip');
                (new Document\Core)->deleteDocuments([$documentEntity->getFileStoreId()]);
            }
            break;
        }
    }

    public function processLambdaMerchantMasterFIRS(array $input)
    {
        $this->trace->info(TraceCode::LAMBDA_REQUEST,
            [
                'input'   => $input
            ]);

        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(3000);
        RuntimeManager::setMaxExecTime(6000);

        $configKey = true;

        list($file, $locationType) = $this->getFileDetails($input,'rbl_merchant_master',$configKey);

        $fileDetails = $this->fileProcessor->getFileDetails($file, $locationType, false);

        $handler = fopen($fileDetails['file_path'],"r");

        $merchantDetailsMap = $this->fetchMerchantDetailsMapFromFile($handler);

        fseek($handler,0);
        $headers = fgetcsv($handler);
        $finalHeaders = self::$headers;
        $data=array();

        while (!feof($handler))
        {
            $row = fgetcsv($handler);
            if($row[0]=='')
                continue;
            $merchantBankDetail = $merchantDetailsMap[$row[0]];

            //Adding the Merchant Bank Details
            $row[23] = (string) $merchantBankDetail[DetailEntity::BANK_ACCOUNT_NAME];
            $row[24] = (string) $merchantBankDetail[DetailEntity::BANK_ACCOUNT_NUMBER];
            $row[25] = (string) $merchantBankDetail[DetailEntity::BANK_BENEFICIARY_ADDRESS1].' '.$merchantBankDetail[DetailEntity::BANK_BENEFICIARY_ADDRESS2].' '.$merchantBankDetail[DetailEntity::BANK_BENEFICIARY_ADDRESS3];
            $row[26] = (string) $merchantBankDetail[DetailEntity::BANK_BRANCH_IFSC];

            $line = array_combine($finalHeaders,array_slice($row,1));
            array_push($data,$line);
        }
        $month = date('m');
        $year = date('y');
        if($month==1)
        {
            $month = 12;
            $year = $year-1;
        }
        else{
            $month = $month -1;
        }
        $fileName = 'FIRS Merchants_'.$month.$year;
        fclose($handler);

        $creator = new FileStore\Creator;
        $creator->extension(FileStore\Format::XLSX)
            ->content($data)
            ->name($fileName)
            ->store(FileStore\Store::S3)
            ->type(FileStore\Type::RBL_MERCHANT_MASTER_FIRS)
            ->save();

        $this->pushFilesToSFTP($creator);

        $response = [
            $creator->getSignedUrl(),
        ];

        return $response;
    }

    protected function fetchMerchantDetailsMapFromFile( $fileHandler)
    {
        $headers = fgetcsv($fileHandler);
        $merchantIds=array();

        while(! feof($fileHandler))
        {
            $row = fgetcsv($fileHandler);
            if($row[0]=='')
                continue;
            array_push($merchantIds,$row[0]);
        }

        $distinctMerchantIds = array_unique($merchantIds);

        $splitIds = array_chunk($distinctMerchantIds,1000);
        $merchantDetailsMap=[];

        foreach ($splitIds as $setIds)
        {
            $merchantBankDetails = $this->repo->merchant_detail->findMerchantBankDetailsWithIds($setIds);
            foreach ($merchantBankDetails as $merchantDetail)
            {
                $merchantId = $merchantDetail[Entity::MERCHANT_ID];
                if(isset($merchantDetailsMap[$merchantId]) === false)
                {
                    $merchantDetailsMap[$merchantId]=[];
                }
                $merchantDetailsMap[$merchantId] = $merchantDetail;
            }
        }

        return $merchantDetailsMap;
    }

    protected function pushFilesToSFTP(FileStore\Creator $creator)
    {
        $bucketConfig = $creator->getBucketConfig(FileStore\Type::RBL_MERCHANT_MASTER_FIRS);

        $data =  [
            BeamService::BEAM_PUSH_FILES   => [$creator->getFullFileName()],
            BeamService::BEAM_PUSH_JOBNAME => BeamConstants::RBL_MERCHANT_MASTER_FIRS_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        $timelines = [];

        $mailInfo = [
            'fileInfo'  => [$creator->getFullFileName()],
            'channel'   => 'RBL',
            'filetype'  => FileStore\Type::RBL_MERCHANT_MASTER_FIRS,
            'subject'   => 'File send failure',
            'recipient' => Constants::MAIL_ADDRESSES[Constants::CROSS_BORDER_TECH]
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
    }
}
