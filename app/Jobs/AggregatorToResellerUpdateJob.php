<?php


namespace RZP\Jobs;


use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class AggregatorToResellerUpdateJob extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 2;

    protected $queueConfigKey = 'commission';

    protected $merchantIds;

    public function __construct(array $merchantIds)
    {
        parent::__construct();

        $this->merchantIds = $merchantIds;
    }

    public function handle()
    {
        parent::handle();

        $traceInfo = ['merchant_ids' => $this->merchantIds];

        $this->trace->info(
            TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_JOB_REQUEST,
            $traceInfo
        );

        $failedMerchantIds = [];

        $core = new Merchant\Core;

        foreach ($this->merchantIds as $merchantId) {
            try
            {
                $core->updateAggregatorToReseller($merchantId);
            }
            catch (\Throwable $e) {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_JOB_FAILED,
                    ['merchant_id' => $merchantId]
                );

                $failedMerchantIds[] = $merchantId;
            }
        }

        $this->delete();

        if (count($failedMerchantIds) > 0)
        {
            $this->checkRetry($failedMerchantIds);
        }
    }

    protected function checkRetry(array $failedMerchantIds)
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_JOB_DELETE, [
                'id'           => $failedMerchantIds,
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
