<?php

namespace RZP\Models\FundTransfer\Base\Initiator;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;

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

        $this->trace->info(
            TraceCode::BATCH_FUND_TRANSFER_FILE_DETAIL_UPDATED,
            [
                'batch_fund_tranfer_id' => $this->batchFundTransfer->getId(),
                'file_url'              => $fileUrl
            ]);
    }

    protected function saveEntitiesToDb(Base\PublicCollection $attempts)
    {
        foreach ($attempts as $attempt)
        {
            $this->repo->saveOrFail($attempt);

            $this->postFtaInitiateProcess($attempt);

            $this->trace->info(TraceCode::FUND_TRANSFER_ATTEMPT_UPDATED, ['fta_id' => $attempt->getId()]);

            $this->trackAttemptsInitiatedSuccess($this->channel, $this->purpose, $attempt->getSourceType());
        }
    }

    public function getBucketConfig(string $type, string $env)
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName($type, $env);

        return $config[$bucketType];
    }
}
