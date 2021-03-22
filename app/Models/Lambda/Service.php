<?php

namespace RZP\Models\Lambda;

use File;
use Request;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FundTransfer\Kotak;
use RZP\Reconciliator\FileProcessor;
use Symfony\Component\HttpFoundation;

class Service extends Base\Service
{
    use Kotak\FileHandlerTrait;

    const MUTEX_RESOURCE = 'LAMBDA_REQUEST_%s';

    // 30 minutes
    const MUTEX_LOCK_TIMEOUT = 1800;

    protected $fileProcessor = null;

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
                }

                if (empty($input['region']) === false)
                {
                    $bucketRegion = $input['region'];
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
}
