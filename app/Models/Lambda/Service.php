<?php

namespace RZP\Models\Lambda;

use File;
use Request;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Gateway\File\Constants as GatewayConstants;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\UfhService;
use RZP\Models\Merchant\Document;
use RZP\Models\FundTransfer\Kotak;
use RZP\Reconciliator\FileProcessor;
use Symfony\Component\HttpFoundation;
use RZP\Models\Merchant\Document\Entity;

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

    protected function getFileDetails(array & $input, string $type)
    {
        if (isset($input['key']) === true)
        {
            // TODO: add validation for key
            $key = urldecode($input['key']);

            $target = $input['gateway'] ?? null;

            if (($type === Batch\Type::NACH) or
                (in_array($target, self::SFTP_BUCKET_TARGETS, true)))
            {
                $filePath = $this->getH2HFileFromAws($key, true, 'sftp_bucket', 'ap-south-1');
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

                $filePath = $this->getH2HFileFromAws($key, true, $bucketConfig, $bucketRegion);
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
            (in_array($input['type'], self::SKIP_INPUT_EXTRACT, true) === false))
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

        list($file, $locationType) = $this->getFileDetails($input,'FIRS');

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

        list($company, $gatewayMerchantId, $date) = explode('_',$filename);

        $part = str_split($date,2);

        $terminal = $this->repo->terminal->findMerchantIdByGatewayMerchantID($gatewayMerchantId);
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

        $this->deleteExistingZipFile($merchantId,$part);

        $document = (new Document\Core)->saveInMerchantDocument($response,$merchantId,$type,$documentDate);

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
                $ufhService->deleteFile($documentEntity->getPublicFileStoreId());
                (new Document\Core)->deleteDocuments([$documentEntity->getFileStoreId()]);
            }
            break;
        }
    }


}
