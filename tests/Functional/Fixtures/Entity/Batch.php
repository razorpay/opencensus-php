<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\FileStore;
use RZP\Models\Batch\Type;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Header;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Batch extends Base
{
    const INPUT_FILE_DIR = 'files/filestore/batch/upload';
    const OUTPUT_FILE_DIR = 'files/filestore/batch/download';

    use FileHandlerTrait;

    public function createRefund(array $attributes = array())
    {
        $params = [
            'type'        => Type::REFUND,
            'total_count' => count($attributes),
            'amount'      => $this->getTotalAmount($attributes)
        ];

        $batch = $this->fixtures->create('batch', $params);

        $this->writeToExcelFile($attributes, $batch->getId(), self::INPUT_FILE_DIR);

        $this->fixtures->create(
                            'file_store',
                            [
                                'entity_id' => $batch->getId(),
                                'name'      => 'batch/upload/' . $batch->getFileKey(),
                                'location'  => 'batch/upload/' . $batch->getFileKeyWithExt(),
                            ]);

        return $batch;
    }

    public function createRefundWithOneAttempt(array $attributes = array())
    {
        return $this->createBatchEntityWithStatus($attributes, Status::PARTIALLY_PROCESSED, 0, 0, 0);
    }

    public function createRefundWithTwoAttempt(array $attributes = array())
    {
         return $this->createBatchEntityWithStatus($attributes, Status::PARTIALLY_PROCESSED, 1, 0, 1);
    }

    public function createRefundWithThreeAttempt(array $attributes = array())
    {
         return $this->createBatchEntityWithStatus($attributes, Status::PARTIALLY_PROCESSED, 2, 0, 1);
    }

    public function createRefundWithFourAttempt(array $attributes = array())
    {
         return $this->createBatchEntityWithStatus($attributes, Status::PARTIALLY_PROCESSED, 3, 0, 1);
    }

    public function createRefundWithProcessedEntries(array $attributes = array())
    {
         return $this->createBatchEntityWithStatus($attributes, Status::PROCESSED, 3, 1, 0);
    }

    public function createReconWithFailedStatus(array $fileRows = [])
    {
        $params = [
            Entity::GATEWAY         => 'FirstData',
            Entity::TYPE            => Type::RECONCILIATION,
            Entity::STATUS          => Status::FAILED,
            Entity::SUCCESS_COUNT   => 0,
            Entity::FAILURE_COUNT   => 1,
            Entity::PROCESSED_COUNT => 1,
            Entity::TOTAL_COUNT     => 1,
            Entity::FAILURE_REASON  => 'Did not get the reconciliation type for the row in combined reconciliation.',
        ];

        $batch = $this->fixtures->create('batch', $params);

        $this->writeToExcelFile($fileRows, $batch->getId(), self::INPUT_FILE_DIR);

        $this->fixtures->create(
            'file_store',
            [
                'entity_id' => $batch->getId(),
                'type'      => FileStore\Type::RECONCILIATION_BATCH_INPUT,
                'name'      => 'batch/upload/' . $batch->getFileKey(),
                'location'  => 'batch/upload/' . $batch->getFileKeyWithExt(),
            ]);

        return $batch;
    }

    public function createReconWithCreatedStatusAndProcessingTrue(array $fileRows = [])
    {
        $params = [
            Entity::GATEWAY         => 'FirstData',
            Entity::TYPE            => Type::RECONCILIATION,
            Entity::STATUS          => Status::CREATED,
            Entity::PROCESSING      => true,
            Entity::SUCCESS_COUNT   => 0,
            Entity::FAILURE_COUNT   => 0,
            Entity::PROCESSED_COUNT => 0,
            Entity::TOTAL_COUNT     => 0,
        ];

        $batch = $this->fixtures->create('batch', $params);

        $this->writeToExcelFile($fileRows, $batch->getId(), self::INPUT_FILE_DIR);

        $this->fixtures->create(
            'file_store',
            [
                'entity_id' => $batch->getId(),
                'type'      => FileStore\Type::RECONCILIATION_BATCH_INPUT,
                'name'      => 'batch/upload/' . $batch->getFileKey(),
                'location'  => 'batch/upload/' . $batch->getFileKeyWithExt(),
            ]);

        return $batch;
    }

    public function create(array $attributes = array())
    {
        $defaultValues = array(
            'created_at' => time() - 10,
            'updated_at' => time() - 5,
            );

        $attributes = array_merge($defaultValues, $attributes);

        $batch = parent::create($attributes);

        return $batch;
    }

    public function createLinkedAccountReversal(array $attributes = array())
    {
        $params = [
            'type'        => Type::LINKED_ACCOUNT_REVERSAL,
            'total_count' => count($attributes),
            'amount'      => $this->getTotalAmount($attributes),
            'merchant_id' => '10000000000002',
        ];

        $batch = $this->fixtures->create('batch', $params);

        $this->writeToExcelFile($attributes, $batch->getId(), self::INPUT_FILE_DIR);

        $this->fixtures->create(
                            'file_store',
                            [
                                'entity_id'     => $batch->getId(),
                                'name'          => 'batch/upload/' . $batch->getFileKey(),
                                'location'      => 'batch/upload/' . $batch->getFileKeyWithExt(),
                                'merchant_id'   => '10000000000002',
                            ]);

        return $batch;
    }

    protected function createBatchEntityWithStatus($attributes, $status, $attempts, $successCount, $failureCount)
    {
        $batch = $this->fixtures->create('batch:refund', $attributes);

        $batch->setStatus($status);
        $batch->setAttempts($attempts);
        $batch->setSuccessCount($successCount);
        $batch->setFailureCount($failureCount);

        $batch->saveOrFail();

        // Creates the processed UFH file

        $processedttributes = $this->createProcessedAttributes($attributes);

        $this->writeToExcelFile($processedttributes, $batch->getId(), self::OUTPUT_FILE_DIR);

        $this->fixtures->create(
                            'file_store',
                            [
                                'entity_id' => $batch->getId(),
                                'name'      => 'batch/upload/' . $batch->getFileKey(),
                                'location'  => 'batch/upload/' . $batch->getFileKeyWithExt(),
                            ]);

        return $batch;
    }

    protected function createTempFile($url)
    {
        $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $uploadedFile = new UploadedFile(
                               $url,
                               'file',
                               $mimeType,
                               null,
                               true);
       return $uploadedFile;
    }

    protected function createProcessedAttributes(array $attributes = array())
    {
        $processedttributes = [];

        foreach ($attributes as $attribute)
        {
            $entry = [
                Header::PAYMENT_ID        => $attribute[Header::PAYMENT_ID],
                Header::AMOUNT            => $attribute[Header::AMOUNT],
                Header::REFUND_ID         => '',
                Header::REFUNDED_AMOUNT   => 0,
                Header::STATUS            => Status::FAILURE,
                Header::ERROR_CODE        => '',
                Header::ERROR_DESCRIPTION => '',
            ];

            array_push($processedttributes, $entry);
        }

        return $processedttributes;
    }

    protected function getTotalAmount(array $attributes = array())
    {
        $totalAmount = 0;

        $amountCol = array_column($attributes, Header::AMOUNT);

        $amountInPaisaCol = array_column($attributes, Header::AMOUNT_IN_PAISE);

        $amountCol = count($amountCol) > 0 ? $amountCol : $amountInPaisaCol;

        $totalAmount = array_sum($amountCol);

        return $totalAmount;
    }
}
