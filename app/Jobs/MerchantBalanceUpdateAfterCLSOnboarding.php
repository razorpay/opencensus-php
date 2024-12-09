<?php

namespace RZP\Jobs;

use RZP\Constants\Metric;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Service as PaymentService;
use RZP\Trace\TraceCode;

class MerchantBalanceUpdateAfterCLSOnboarding extends MerchantBalanceUpdate
{
    const RELEASE_WAIT_SECS    = 30;

    /**
     * @var string
     */
    protected $queueConfigKey = 'merchant_balance_update_after_cls_onboarding';

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            (new PaymentService)->createCorrespondingCLSAdjustment($this->input['payment_id']);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::CLS_ONBOARDING_FAILURE,
                $this->input);

            $this->trace->count(Metric::CLS_ONBOARDING_FAILURE_ADJUSTMENT_CREATION);
        }
    }
}
