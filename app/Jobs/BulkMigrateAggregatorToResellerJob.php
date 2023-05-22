<?php


namespace RZP\Jobs;


use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class BulkMigrateAggregatorToResellerJob extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 1;

    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $merchantIds;
    protected $actorDetails;

    protected $retry;

    /**
     * Create a new job instance.
     * @param $merchantIds    array      An array containing merchant Ids
     * @param $retry          int|null   Number of retries attempted
     *
     * @return void
     */
    public function __construct(array $merchantIds, array $actorDetails, int $retry = null)
    {
        parent::__construct();

        $this->merchantIds = $merchantIds;
        $this->actorDetails= $actorDetails;
        $this->retry       = $retry ?? 0;
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
                $core->migrateAggregatorToResellerPartner($merchantId, $this->actorDetails);
            }
            catch (\Throwable $e) {
                $this->countJobException($e);

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_JOB_FAILED,
                    ['merchant_id' => $merchantId]
                );

                $failedMerchantIds[] = $merchantId;
            }
        }

        if (count($failedMerchantIds) > 0 && $this->retry < self::MAX_RETRY_ATTEMPT)
        {
            BulkMigrateAggregatorToResellerJob::dispatch($failedMerchantIds, $this->actorDetails, $this->retry + 1);
        }

        $this->delete();
    }
}
