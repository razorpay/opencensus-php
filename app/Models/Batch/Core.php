<?php

namespace RZP\Models\Batch;

use Config;
use Mail;
use RZP\Base\RuntimeManager;
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
        $batch = (new Entity)->build($input);

        $batch->merchant()->associate($this->merchant);

        $this->repo->transaction(function () use ($batch, $input)
        {
            $file = $this->processor->saveInputFile($batch, $input[Entity::FILE]);



            $entries = $this->parseExcelSheets($file);

            $batch->getValidator()->validateEntries($entries);

            $this->fillBatchEntityWithInputFileDetails($batch, $entries);

            $this->repo->saveOrFail($batch);
        });

        $this->trace->info(TraceCode::BATCH_CREATED, $batch->toArrayPublic());

        return $batch;
    }

    public function retryBatch(Entity $batch): Entity
    {
        $batch->getValidator()->validateNotProcessedAlready();

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
        $file = ($batch->getStatus() === Status::CREATED) ?
                    $batch->inputFile() : $batch->processedFile();

        // Backward compatibility:
        // - If processed file relation exists use that else to handle BC
        //   form the AWS key and get the signed URL as done previously.

        if ($file === null)
        {
            $signedUrl = $this->getSignedUrlOfBatchFile($batch);
        }
        else
        {
            $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);
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
            $this->processBatch($batch);
        }

        return $batches;
    }

    public function processBatch(Entity $batch)
    {
        try
        {
            $batch->getValidator()->validateNotProcessedAlready();

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

            $batch->setStatus(Status::PROCESSED);

            $this->repo->saveOrFail($batch);
        }

        return $batch;
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
     * Get signed URL of batch file in old way.
     *
     * @param Entity $batch
     *
     * @return string
     */
    protected function getSignedUrlOfBatchFile(Entity $batch): string
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
