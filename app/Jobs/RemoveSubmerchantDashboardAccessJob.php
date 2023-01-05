<?php


namespace RZP\Jobs;


use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class RemoveSubmerchantDashboardAccessJob extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 2;

    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $partnerIds;

    public function __construct(array $partnerIds)
    {
        parent::__construct();

        $this->partnerIds = $partnerIds;
    }

    public function handle()
    {
        parent::handle();

        $traceInfo = ['partner_ids' => $this->partnerIds];

        $this->trace->info(
            TraceCode::REMOVE_SUBMERCHANT_DASHBOARD_ACCESS_JOB_REQUEST,
            $traceInfo
        );

        $failedPartnerIds = [];

        $core = new Merchant\Core;

        foreach ($this->partnerIds as $partnerId) {
            try
            {
                $core->removeSubmerchantDashboardAccessOfPartner($partnerId);
            }
            catch (\Throwable $e) {
                $this->countJobException($e);

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::REMOVE_SUBMERCHANT_DASHBOARD_ACCESS_JOB_FAILED,
                    ['partner_id' => $partnerId]
                );

                $failedPartnerIds[] = $partnerId;
            }
        }

        $this->delete();

        if (count($failedPartnerIds) > 0)
        {
            $this->checkRetry($failedPartnerIds);
        }
    }

    protected function checkRetry(array $failedMerchantIds)
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::REMOVE_SUBMERCHANT_DASHBOARD_ACCESS_JOB_DELETE, [
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
