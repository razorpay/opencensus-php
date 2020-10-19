<?php

namespace RZP\Jobs;

use RZP\Jobs\Job;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\PaymentLink;
use Razorpay\Trace\Logger as Trace;
/**
 * - Asynchronously update payment page, generate receipt etc after a successful payment
 */
class PaymentPageProcessor extends Job
{
    const RETRY_DELAY           = 60;

    const MAX_RETRY_ATTEMPTS    = 5;
    /**
     * {@inheritDoc}
     */
    protected $queueConfigKey = 'payment_page_generic';

    protected $payment;

    protected $core;

    public function __construct(string $mode, Payment\Entity $payment)
    {
        parent::__construct($mode);

        $this->payment = $payment;
    }

    public function handle()
    {
        parent::handle();

        $this->core = new PaymentLink\Core;
        try
        {
            $paymentLink = $this->payment->paymentLink;

            $this->trace->info(
                TraceCode::PAYMENT_LINK_PAYMENT_CAPTURE_QUEUE,
                [
                    'payment_id'     => $this->payment->getId(),
                    'payment_link'   => $paymentLink->getId(),
                ]);

            $this->core->postPaymentCaptureAttemptProcessing($this->payment, true);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                null,
                [
                    'payment_id'     => $this->payment->getId(),
                    'payment_link'   => $paymentLink->getId(),
                ]
            );
        }

        $this->delete();
    }
}
