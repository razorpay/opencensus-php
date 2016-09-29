<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Batch\Type;
use RZP\Models\Batch\Status;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class Batch extends Base
{
    use FileHandlerTrait;

    public function createRefund(array $attributes = array())
    {
        $url = $this->writeToExcelFile($attributes, 'uploaded_file', 'files/batch_file_download');

        $params = [
            'upload_file_url' => $url,
            'type'              => Type::REFUND,
            'total_count'       => count($attributes),
            'amount'            => $this->getTotalAmount($attributes)
        ];

        $batch = $this->fixtures->create('batch', $params);

        $newUrl = $this->writeToExcelFile($attributes, $batch->getId(), 'files/batch_file_download');

        $batch->setUploadFileUrl($newUrl);
        $batch->saveOrFail();

        return $batch;
    }

    public function createRefundWithOneAttempt(array $attributes = array())
    {
        return $this->createProcessedEntity($attributes, Status::PROCESSING, 0, 0, 0);
    }

    public function createRefundWithTwoAttempt(array $attributes = array())
    {
         return $this->createProcessedEntity($attributes, Status::PROCESSING, 1, 0, 1);
    }

    public function createRefundWithThreeAttempt(array $attributes = array())
    {
         return $this->createProcessedEntity($attributes, Status::PROCESSING, 2, 0, 1);
    }

    public function createRefundWithFourAttempt(array $attributes = array())
    {
         return $this->createProcessedEntity($attributes, Status::PROCESSED, 3, 0, 1);
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

    protected function createProcessedEntity($attributes, $status, $attempts, $successCount, $failureCount)
    {
        $batch = $this->fixtures->create('batch:refund', $attributes);

        $processedttributes = $this->createProcessedAttributes($attributes);
        $newUrl = $this->writeToExcelFile($processedttributes, $batch->getId(), 'files/batch_file_download');

        $batch->setStatus($status);
        $batch->setAttempts($attempts);
        $batch->setSuccessCount($successCount);
        $batch->setFailureCount($failureCount);
        $batch->setDownloadFileUrl($newUrl);

        $batch->saveOrFail();

        return $batch;
    }

    // protected function writeToExcelFile($data, $name)
    // {
    //     \Config::set('excel::export.calculate', true);

    //     $columnFormat = $this->getColumnFormatForExcel();

    //     $excel = $this->createExcelObject($data, $name, $columnFormat);

    //     $fileMetadata = $excel->store('xlsx', storage_path('files/batch_file_download'), true);

    //     $fullpath = $fileMetadata['full'];

    //     $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    //     $url = $this->saveToAws($name.'.xlsx', $fullpath, $xlsxMimeType);

    //     return $url;
    // }

    protected function createTempFile($url)
    {
        $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $uploadedFile = new UploadedFile(
                               $url,
                               'file',
                               $mimeType,
                               filesize($url),
                               null,
                               true);
       return $uploadedFile;
    }

    protected function createProcessedAttributes(array $attributes = array())
    {
        $processedttributes = array();
        $headers = array('payment_id', 'refund_amount');

        foreach ($attributes as $attribute)
        {
            $valueMap = array_combine($headers, $attribute);

            $entry = array($valueMap['payment_id'], $valueMap['refund_amount'], '', 0, Status::FAILURE, '', '');

            array_push($processedttributes, $entry);
        }

        return $processedttributes;
    }

    protected function getTotalAmount(array $attributes = array())
    {
        $totalAmount = 0;
        $headers = array('payment_id', 'refund_amount');
        foreach ($attributes as $attribute)
        {
            $valueMap = array_combine($headers, $attribute);

            $totalAmount += $valueMap['refund_amount'];
        }
        return $totalAmount;
    }
}
