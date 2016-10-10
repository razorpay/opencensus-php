<?php

namespace RZP\Models\Batch;

use Mail;
use Config;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Base\RuntimeManager;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class Core extends Base\Core
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Batch_File';

    protected $processor;

    public function __construct()
    {
        parent::__construct();

        $this->processor = new Processor;
    }

    public function create($input)
    {
        $batch = (new Batch\Entity)->build($input);

        $batch->merchant()->associate($this->merchant);

        $entries = $this->parseExcelSheets($input['file']);

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE_ENTRIES, $entries);

        $batch->getValidator()->validateEntries($entries, $batch->getType());

        list($totalCount, $amount) = $this->getFileData($entries);

        $batch->setAmount($amount);

        $batch->setTotalCount($totalCount);

        $awsUrl = $this->processor->saveBatchFileToAws($batch, $input['file']);

        $this->processor->deleteFile($input['file']->getRealPath());

        $batch->setUploadFileUrl($awsUrl);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $batch->toArrayPublic());

        return $batch;
    }

    public function retryBatch(Batch\Entity $batch)
    {
        if (($batch->getStatus() === Status::PROCESSED) and
            ($batch->getFailureCount() === 0))
        {
            $this->trace->info(TraceCode::BATCH_RETRY_FAILURE, $batch->toArrayPublic());

            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_ALREADY_PROCESSED);
        }

        $batch->setStatus(Status::PROCESSING);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_RETRY, $batch->toArrayPublic());

        return $batch;
    }

    public function downloadBatch(Batch\Entity $batch)
    {
        $awsKey = $this->processor->getAwsKey($batch);

        $publicUrl = $this->getPreSignedUrlFromAws($awsKey);

        $this->trace->info(
            TraceCode::BATCH_DOWNLOAD,
            [
                'batch'         => $batch->toArrayPublic(),
                'url'           => $publicUrl,
            ]);

        return $publicUrl;
    }

    public function processBatches()
    {
        $this->increaseAllowedSystemLimits();

        $batches = $this->repo->batch->findUnprocessedEntries();

        foreach ($batches as $batch)
        {
            try
            {
                $batch->incrementAttempts();

                $this->processor->process($batch);
            }
            catch (\Exception $e)
            {
                $this->trace->warning(
                    TraceCode::BATCH_PROCESSING_ERROR,
                    [
                        'batch'         => $batch->toArrayPublic(),
                    ]);

                if ($batch->getAttempts() >= 3)
                {
                    $batch->setStatus(Status::PROCESSED);
                }
                else
                {
                    $batch->setStatus(Status::PROCESSING);
                }

                $this->repo->saveOrFail($batch);
            }
        }
        return $batches;
    }

    protected function getFileData($entries)
    {
        $totalAmount = 0;

        $totalEntries = count($entries);

        foreach ($entries as $entry)
        {
            $totalAmount += $entry[Header::AMOUNT];
        }

        return array($totalEntries, $totalAmount);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(1000);
    }
}
