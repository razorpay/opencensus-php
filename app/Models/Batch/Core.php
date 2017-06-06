<?php

namespace RZP\Models\Batch;

use Config;
use Mail;
use RZP\Base\RuntimeManager;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;

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

    public function create($input): Entity
    {
        // Build the entity and associates relations

        $batch = (new Entity)->build($input);

        $batch->merchant()->associate($this->merchant);

        // Parses input file and validates according to batch type.

        $entries = $this->parseExcelSheets($input['file']);

        $batch->getValidator()->validateEntries($entries, $batch->getType());

        // Fills batch entity with relevant details of input file.

        $this->fillBatchEntityWithInputFileDetails($batch, $entries);

        // Saves input file

        $this->processor->saveInputFile($batch, $input[Entity::FILE]);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $batch->toArrayPublic());

        return $batch;
    }

    public function retryBatch(Entity $batch): Entity
    {
        if (($batch->getStatus() === Status::PROCESSED) and
            ($batch->getFailureCount() === 0))
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_ALREADY_PROCESSED);
        }

        $batch->setStatus(Status::PROCESSING);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_RETRY, $batch->toArrayPublic());

        return $batch;
    }

    /**
     * Returns signed url of the processed batch file.
     *
     * @param Entity $batch
     *
     * @return string
     */
    public function downloadBatch(Entity $batch): string
    {
        $processedFile = $batch->processedFile;

        // Backward compatibility:
        // - If processed file relation exists use that else to handle BC
        //   form the AWS key and get the signed URL as done previously.

        if ($processedFile === null)
        {
            $signedUrl = $this->getSignedUrlOrBatchFile($batch);
        }
        else
        {
            $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($processedFile);
        }

        $this->trace->info(
            TraceCode::BATCH_DOWNLOAD,
            [
                'batch_id' => $batch->getId(),
                'url'      => $signedUrl,
            ]);

        return $signedUrl;
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
                $this->trace->traceException(
                    $e,
                    Trace::WARNING,
                    TraceCode::BATCH_PROCESSING_ERROR,
                    [
                        'batch' => $batch->toArrayPublic(),
                    ]);

                // TODO: Remove this comment. Currently we will mark the final state as processed.
                // if ($batch->getAttempts() >= 3)
                // {
                //     $batch->setStatus(Status::PROCESSED);
                // }
                // else
                // {
                //     $batch->setStatus(Status::PROCESSING);
                // }

                $batch->setStatus(Status::PROCESSED);

                $this->repo->saveOrFail($batch);
            }
        }

        return $batches;
    }

    /**
     * Fills Batch entity with details extracted from the input file.
     * Eg.
     * - Total row count
     * - Aggregate sum of amount field
     *
     * @param Entity $batch
     * @param array  $entries
     */
    protected function fillBatchEntityWithInputFileDetails(
        Entity $batch,
        array $entries)
    {
        $totalAmount = array_sum(array_column($entries, Header::AMOUNT));
        $totalCount  = count($entries);

        $batch->setAmount($totalAmount);
        $batch->setTotalCount($totalCount);
    }

    /**
     * @deprecated
     *
     * Get signed URL of batch file in old way.
     *
     * @param Entity $batch
     *
     * @return string
     */
    protected function getSignedUrlOrBatchFile(Batch\Entity $batch): string
    {
        $awsKey = $batch->getFilePrefix() . $batch->getFileKeyWithExt();

        return $this->getPreSignedUrlFromAws($awsKey);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(1000);
    }
}
