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

    public function fetchMultiple($input)
    {
        $batches = $this->repo->batch->fetch($input, $this->merchant->getId());

        $this->trace->info(TraceCode::BATCH_LIST, $batches->toArrayPublic());

        return $batches->toArrayPublic();
    }

    public function getBatchById($id)
    {
        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        $this->trace->info(TraceCode::BATCH_GET, $batch->toArrayPublic());

        return $batch->toArrayPublic();
    }

    public function retryBatch($id)
    {
        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        $batch = (new Batch\Core)->retryBatch($batch);

        return $batch->toArrayPublic();
    }

    public function downloadBatch($id)
    {
        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        $awsPublicUrl = (new Batch\Core)->downloadBatch($batch);

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
