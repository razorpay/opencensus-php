<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class Core extends Base\Core
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Batch_File';

    public function create($input)
    {
        $batch = (new Batch\Entity)->build($input);

        $batch->merchant()->associate($this->merchant);

        $entries = $this->parseExcelFile($input['file']);

        $batch->getValidator()->validateEntries($entries);

        list($totalCount, $amount) = $this->getFileData($batch, $entries);

        $batch->setAmount($amount);

        $batch->setTotalCount($totalCount);

        $awsUrl = $this->saveBatchFileToAws($batch, $input['file']);

        $batch->setUploadFileUrl($awsUrl);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $batchRefund->toArrayPublic());

        return $batch;
    }

    public function getBatches()
    {
        $merchant = $this->merchant;

        $batches = $this->repo->batch->fetch($input, $merchant->getId());

        $this->trace->info(TraceCode::BATCH_LIST, $batches->toArrayPublic());

        return $batches;
    }

    public function getBatchById($id)
    {
        $batch = $this->repo->batch->findOrFail($id);

        $this->trace->info(TraceCode::BATCH_GET, $batch->toArrayPublic());

        return $batch;
    }

    public function retryBatch($id)
    {
        $batch = $this->repo->batch->findOrFail($id);

        if ($batch->getStatus() === Status::PROCESSED)
        {
            $this->trace->error(TraceCode::BATCH_RETRY, $batch->toArrayPublic());

            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_FILE_ALREADY_PROCESSED);
        }

        $batch->setStatus(Status::FAILURE);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_RETRY, $batch->toArrayPublic());

        return $batch;
    }

    public function downloadBatch($id)
    {
        $batch = $this->repo->batch->findOrFail($id);

        $storagePath = storage_path('files/batch_download');
        $filePath = $storagePath . '/' . $batch->getId() . '.xlsx';

        $bucket = $this->getBucketName($batch);

        $publicUrl = $this->getPreSignedUrlFromAws($bucket, $id.'.xlsx', $filePath);

        $this->trace->info(
            TraceCode::BATCH_DOWNLOAD,
            [
                'batch'         => $batch->toArrayPublic(),
                'url'           => $publicUrl,
            ]);

        return $publicUrl;
    }

    protected function getFileData($batch, $entries)
    {
        $totalAmount = 0;

        $totalEntries = count($entries);

        $headers = $this->getHeaders($batch);

        foreach ($entries as $entry)
        {
            $entryMap = array_combine($headers, $entry);

            $totalAmount += $entryMap['Amount'];
        }

        return array($totalEntries, $totalAmount);
    }

    protected function saveBatchFileToAws($batch, $file)
    {
        $bucket = getBucketName($batch);

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $url = $this->saveToAws($batch->getId().'.xlsx', $file, $xlsxMimeType, $bucket);

        return $url;
    }

    protected function getBatchFileFromAws($batch)
    {
        $storagePath = storage_path('files/batch_file_download');
        $filePath = $storagePath . '/' . $batch->getId() . '.xlsx';

        $bucket = getBucketName($batch);

        return $this->getFileFromAws($bucket, $batch->getId().'.xlsx', $filePath);
    }

    protected function getBucketName($batch)
    {
        if ($batch->getStatus() === Status::CREATED)
        {
            return 'batch_upload_bucket';
        }
        else
        {
            return 'batch_download_bucket';
        }
    }

    protected function getHeaders($batch)
    {
        if ($batch->getStatus === Status::CREATED)
        {
            return Batch\Type::getInputHeaders($batch->getType());
        }
        else
        {
            return Batch\Type::getOutputHeaders($batch->getType());
        }
    }
}
