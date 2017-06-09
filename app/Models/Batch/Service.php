<?php

namespace RZP\Models\Batch;

use Config;
use Mail;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Service extends Base\Service
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Batch_File';

    protected $merchant;

    public function createBatch($input)
    {
        $batch = (new Core)->create($input);

        return $batch->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $batches = $this->repo->batch->fetch($input, $this->merchant->getId());

        return $batches->toArrayPublic();
    }

    public function getBatchById($id)
    {
        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        return $batch->toArrayPublic();
    }

    public function retryBatch($id)
    {
        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        $batch = (new Core)->retryBatch($batch);

        return $batch->toArrayPublic();
    }

    public function downloadBatch($id)
    {
        $batch = $this->repo->batch->findByPublicIdAndMerchant($id, $this->merchant);

        $awsPublicUrl = (new Core)->downloadBatch($batch);

        $responseObj = [
            'url' => $awsPublicUrl,
        ];

        return $responseObj;
    }

    public function processBatches()
    {
        $batches = (new Core)->processBatches();

        return $batches->toArrayPublic();
    }

    public function processBatch(string $id): array
    {
        $batch = $this->repo->batch->findByPublicId($id);

        $batch = (new Core)->processBatch($batch);

        return $batch->toArrayPublic();
    }
}
