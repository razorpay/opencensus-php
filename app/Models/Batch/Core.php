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
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class Core extends Base\Core
{
    use FileHandlerTrait;


    protected static $fileToReadName = 'Batch_File';

    public function create($input)
    {
        $batch = (new Batch\Entity)->build($input);

        $file = $input['file'];

        $extension = $file->getClientOriginalExtension();
        if(empty($extension) === true)
        {
            $extension = pathinfo($file)['extension'];
        }

        $mimeType = $file->getMimeType();

        $batch->getValidator()->validateExtension($mimeType, $extension);

        $batch->getValidator()->validateSize($file);

        $batch->merchant()->associate($this->merchant);

        $entries = $this->parseExcelSheets($input['file']);

        $batch->getValidator()->validateEntries($entries, $batch->getType());

        list($totalCount, $amount) = $this->getFileData($batch, $entries);

        $batch->setAmount($amount);

        $batch->setTotalCount($totalCount);

        $awsUrl = $this->saveBatchFileToAws($batch, $input['file']);

        $batch->setUploadFileUrl($awsUrl);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $batch->toArrayPublic());

        return $batch;
    }

    public function retryBatch($batch)
    {
        if ($batch->getStatus() === Status::PROCESSED)
        {
            $this->trace->error(TraceCode::BATCH_RETRY, $batch->toArrayPublic());

            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_FILE_ALREADY_PROCESSED);
        }

        $batch->setStatus(Status::PROCESSING);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_RETRY, $batch->toArrayPublic());

        return $batch;
    }

    public function downloadBatch($batch)
    {
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

    public function processBatches()
    {
        $batches = $this->repo->batch->findUnprocessedEntries();

        foreach ($batches as $batch)
        {
            (new Processor)->process($batch);
        }

        return $batches;
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

    protected function getHeaders($batch)
    {
        if ($batch->getStatus() === Status::CREATED)
        {
            return Batch\Type::getInputHeaders($batch->getType());
        }
        else
        {
            return Batch\Type::getOutputHeaders($batch->getType());
        }
    }

    protected function saveBatchFileToAws($batch, $file)
    {
        $bucket = $this->getBucketName($batch);

        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $url = $this->saveToAws($batch->getId() . '.xlsx', $file->getPathName(), $xlsxMimeType, $bucket);

        return $url;
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
}
