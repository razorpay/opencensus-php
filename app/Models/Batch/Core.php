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

        $awsUrl = $this->saveBatchFile($batch, $input['file']);

        $batch->setUploadFileUrl($awsUrl);

        $this->repo->saveOrFail($batch);

        $this->trace->info(TraceCode::BATCH_UPLOAD_FILE, $batchRefund->toArrayPublic());

        return $batch;
    }

    protected function getFileData($batch, $entries)
    {
        $totalAmount = 0;

        $totalEntries = count($entries);

        $headers = Batch\Type::getInputHeaders($batch->getType());

        foreach ($entries as $entry)
        {
            $entryMap = array_combine($headers, $entry);

            $totalAmount += $entryMap['Amount'];
        }

        return array($totalEntries, $totalAmount);
    }

    protected function saveBatchFile($batch, $file)
    {
        $xlsxMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $bucket = 'batch_upload_bucket';

        $url = $this->saveToAws($batch->getId().'.xlsx', $file, $xlsxMimeType, $bucket);

        return $url;
    }
}
