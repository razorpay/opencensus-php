<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Metric;
use Razorpay\Trace\Logger as Trace;

class SubMerchantTaggingJob extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $partnerId;

    protected $subMerchantId;

    public function __construct($mode, string $partnerId, string $subMerchantId)
    {
        parent::__construct($mode);
        $this->subMerchantId = $subMerchantId;
        $this->partnerId     = $partnerId;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::SUBMERCHANT_TAGGING_ASYNC_JOB,
            [
                'partner_id'  => $this->partnerId,
                'merchant_id' => $this->subMerchantId,
            ]
        );

        try
        {
            $partner     = $this->repoManager->merchant->findOrFailPublic($this->partnerId);
            $subMerchant = $this->repoManager->merchant->findOrFailPublic($this->subMerchantId);
            (new Merchant\Core())->addSubMerchantReferral($partner, $subMerchant);
            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SUBMERCHANT_TAGGING_ASYNC_JOB_FAILED,
                [
                    'partner_id'  => $this->partnerId,
                    'merchant_id' => $this->subMerchantId,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::SUBMERCHANT_TAGGING_ASYNC_JOB_MESSAGE_DELETE, [
                'partner_id'   => $this->partnerId,
                'merchant_id'  => $this->subMerchantId,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->trace->count(Metric::SUBMERCHANT_TAGGING_FAILURE_TOTAL, []);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
