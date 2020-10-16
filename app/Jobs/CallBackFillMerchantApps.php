<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core as MerchantCore;

class CallBackFillMerchantApps extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 1;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    public $timeout = 1000;

    public $limit;

    public $afterId;

    public $merchantIds;

    public function __construct(string $mode, array $merchantIds = null, $limit = null, $afterId = null)
    {
        parent::__construct($mode);

        $this->merchantIds = $merchantIds;

        $this->limit = $limit;

        $this->afterId = $afterId;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::CALL_BACK_FILL_MERCHANT_APPS_REQUEST,
                [
                    'limit'         => $this->limit,
                    'afterId'       => $this->afterId,
                    'merchant_ids'  => $this->merchantIds,
                ]
            );

            (new MerchantCore())->backFillMerchantApplications($this->merchantIds, $this->limit, $this->afterId);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CALL_BACK_FILL_MERCHANT_APPS_ERROR,
                [
                    'limit'         => $this->limit,
                    'afterId'       => $this->afterId,
                    'merchant_ids'  => $this->merchantIds,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::CALL_BACK_FILL_MERCHANT_APPS_QUEUE_DELETE, [
                'id'           => $this->merchantIds,
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
