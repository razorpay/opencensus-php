<?php


namespace RZP\Jobs;


use Razorpay\Trace\Logger as Trace;
use RZP\Models\Partner;
use RZP\Trace\TraceCode;

class BulkMigrateResellerToAggregatorJob extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 1;

    protected $queueConfigKey = 'commission';

    protected $requestParams;
    protected $actorDetails;
    protected $retry;

    /**
     * Create a new job instance.
     * @param $requestParams  array[ 'merchant_id' => string, 'new_auth_create' => bool ]  An associative array containing params to be set in
     *                                                                                     the requestParams instance variable that is required to run the job
     * @param $retry          int|null   Number of retries attempted
     *
     * @return void
     */
    public function __construct(array $requestParams, array $actorDetails, int $retry = null)
    {
        parent::__construct();

        $this->requestParams = $requestParams;
        $this->actorDetails  = $actorDetails;
        $this->retry         = $retry ?? 0;
    }

    public function handle()
    {
        parent::handle();

        $traceInfo = ['request_params' => $this->requestParams];
        $this->trace->info(
            TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_JOB_REQUEST,
            $traceInfo
        );

        $failedParams = [];

        $core = new Partner\Core();

        foreach ($this->requestParams as $param) {
            try
            {
                $core->migrateResellerToAggregatorPartner($param, $this->actorDetails);
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

                $failedParams[] = $param;
            }
        }

        if (count($failedParams) > 0 && $this->retry < self::MAX_RETRY_ATTEMPT)
        {
            BulkMigrateResellerToAggregatorJob::dispatch($failedParams, $this->actorDetails, $this->retry + 1);
        }

        $this->delete();
    }
}
