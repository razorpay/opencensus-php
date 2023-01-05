<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Stakeholder;

class SyncStakeholder extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    public $timeout = 1800;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $merchantIds;

    public function __construct(string $mode, $merchantIds)
    {
        parent::__construct($mode);

        $this->merchantIds = $merchantIds;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::MERCHANT_STAKEHOLDER_SYNC, ['mids' => $this->merchantIds]);

        try {
            $core = new Stakeholder\Core;

            foreach ($this->merchantIds as $merchantId)
            {
                /**
                 * @var $merchantDetails Detail\Entity
                 */
                $merchantDetails = $this->repoManager->merchant_detail->findOrFail($merchantId);
                $core->createOrFetchStakeholder($merchantDetails);
            }

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MERCHANT_STAKEHOLDER_SYNC_JOB_ERROR,
                [
                    'mode' => $this->mode,
                ]
            );

            $this->checkRetry($e);
        }
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::MERCHANT_STAKEHOLDER_SYNC_JOB_DELETE, [
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
