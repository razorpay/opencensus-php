<?php

namespace RZP\Models\Batch;

use Mail;
use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;
use RZP\Models\Batch\Status;

class Service extends Base\Service
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Batch_File';

    protected $merchant;

    public function createBatch($input)
    {
        $batch = (new Batch\Core)->create($input);

        return $batch->toArrayPublic();
    }

    public function getBatches($input)
    {
        $batches = (new Batch\Core)->getBatches($input);

        return $batches->toArrayPublic();
    }

    public function getBatchById($id)
    {
        $batch = (new Batch\Core)->getBatchById($id);

        return $batch->toArrayPublic();
    }

    public function retryBatch($id)
    {
        $batch = (new Batch\Core)->retryBatch($id);

        return $batch->toArrayPublic();
    }

    public function downloadBatch($id)
    {
        $awsPublicUrl = (new Batch\Core)->downloadBatch($id);

        $responseObj = [
            'url' => $awsPublicUrl,
        ];

        return $responseObj;
    }

    public function processBatch()
    {
        $batches = (new Batch\Core)->processBatches();

        return $batches->toArrayPublic();
    }
}
