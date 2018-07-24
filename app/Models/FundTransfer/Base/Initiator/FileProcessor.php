<?php

namespace RZP\Models\FundTransfer\Base\Initiator;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore;

abstract class FileProcessor extends NodalAccount
{
    public function initiateTransfer(Base\PublicCollection $attempts): array
    {
        $this->updateAttemptStatus($attempts);

        $fileEntity = $this->generateFundTransferFile($attempts);

        $this->updateFundTransferFileDetails($fileEntity);

        $this->saveEntitiesToDb($attempts);

        return [
            'file' => $fileEntity->get()
        ];
    }

    protected function updateFundTransferFileDetails(FileStore\Creator $fileEntity)
    {
        $urls = [
            'file' => $fileEntity->getUrl()
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
        }
    }
}
