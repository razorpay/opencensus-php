<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\FileStore\Accessor;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Activation;

class InvoiceBucketUpdater extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    public $timeout = 1800;

    protected $fileStoreIds;

    protected $bucketConfig;

    public function __construct(string $mode, array $fileStoreIds, $bucketConfig)
    {
        parent::__construct($mode);

        $this->fileStoreIds = $fileStoreIds;
        $this->bucketConfig = $bucketConfig;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::INVOICE_BUCKET_UPDATER_JOB_REQUEST, [
            'file_ids' => $this->fileStoreIds
        ]);

        try
        {
            $accessor = new Accessor();

            $files = $this->repoManager->file_store->findMany($this->fileStoreIds);

            foreach ($files as $file)
            {
                $accessor->updateBucketNameAndRegion($file, $this->bucketConfig);
            }

            $this->trace->info(TraceCode::INVOICES_MIGRATED_SUCCESSFULLY, [
                'file_ids' => $this->fileStoreIds
            ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_INVOICE_BUCKET_UPDATE_FAILED,
                [
                    'mode' => $this->mode,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::COMMISSION_INVOICE_BUCKET_UPDATE_JOB_DELETE, [
                'id'           => $this->fileStoreIds,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
