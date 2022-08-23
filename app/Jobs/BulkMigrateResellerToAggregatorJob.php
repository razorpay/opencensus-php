<?php


namespace RZP\Jobs;


use Razorpay\Trace\Logger as Trace;
use RZP\Models\Partner;
use RZP\Trace\TraceCode;

class BulkMigrateResellerToAggregatorJob extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 2;

    protected $queueConfigKey = 'commission';

    protected $requestParams;

    /**
     * Create a new job instance.
     * @param $requestParams  array[ 'merchant_id' => string, 'new_auth_create' => bool ]  An associative array containing params to be set in
     *                                                                                     the requestParams instance variable that is required to run the job
     *
     * @return void
     */
    public function __construct(array $requestParams)
    {
        parent::__construct();

        $this->requestParams = $requestParams;
    }

    public function handle()
    {
        parent::handle();

        $traceInfo = ['request_params' => $this->requestParams];

        $this->trace->info(
            TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_JOB_REQUEST,
            $traceInfo
        );

        $failedMerchantIds = [];

        $core = new Partner\Core();

        foreach ($this->requestParams as $param) {
            try
            {
                $success = $core->migrateResellerToAggregatorPartner($param);
                if ($success === false)
                {
                    $failedMerchantIds[] = $param['merchant_id'];
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_JOB_FAILED,
                    [
                        'merchant_id' => $param['merchant_id'],
                        'new_auth_create' => $param['new_auth_create']
                    ]
                );

                $failedMerchantIds[] = $param['merchant_id'];
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
            $this->trace->error(TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_JOB_DELETE, [
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
