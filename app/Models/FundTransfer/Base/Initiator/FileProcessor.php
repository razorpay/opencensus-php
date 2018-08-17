<?php

namespace RZP\Models\FundTransfer\Base\Initiator;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Trace\TraceCode;

abstract class FileProcessor extends NodalAccount
{
    public function process(Base\PublicCollection $attempts): array
    {
        $fileEntity = $this->generateFundTransferFile($attempts);

        $this->trace->info(TraceCode::FTA_FILE_CREATED);

        $this->updateFundTransferFileDetails($fileEntity);

        $this->trace->info(TraceCode::FTA_BATCH_UPDATED);

        $this->saveEntitiesToDb($attempts);

        $this->trace->info(TraceCode::FTA_SAVED_TO_DB);

        return [
            'file' => $fileEntity->get()
        ];
    }

    protected function updateFundTransferFileDetails(FileStore\Creator $fileEntity)
    {
        $fileUrl = null;

        try
        {
            $fileUrl = $fileEntity->getUrl();
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex);
        }

        $urls = [
            'file' => $fileUrl
        ];

        $fileDetails = $fileEntity->get();

        if ($this->batchFundTransfer === null)
        {
            throw new Exception\LogicException(
                'Update file details for Batch Settlement attempted before entity creation',
                null,
                [
                    'details' => $fileDetails
                ]);
        }

        $this->batchFundTransfer->setUrls($urls);

        $this->batchFundTransfer->setTxtFileId($fileDetails['id']);

        $this->repo->saveOrFail($this->batchFundTransfer);
    }

    protected function saveEntitiesToDb(Base\PublicCollection $attempts)
    {
        foreach ($attempts as $attempt)
        {
            $this->repo->saveOrFail($attempt);

            $this->repo->saveOrFail($attempt->source);

            $this->trackAttemptsInitiatedSuccess($this->channel, $this->purpose, $attempt->getSourceType());
        }
    }
}
