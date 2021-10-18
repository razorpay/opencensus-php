<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\PaymentLink;
use Illuminate\Support\Str;

/**
 * - Asynchronously update payment page, generate receipt etc after a successful payment
 */
class PaymentPageProcessor extends Job
{
    const RETRY_DELAY           = 60;
    const MAX_RETRY_ATTEMPTS    = 5;

    const PAYMENT_CAPTURE_EVENT     = 'PAYMENT_CAPTURE_EVENT';
    const REFUND_PROCESSED_EVENT    = 'REFUND_PROCESSED_EVENT';

    /**
     * {@inheritDoc}
     */
    protected $queueConfigKey = 'payment_page_generic';

    /**
     * @var \RZP\Models\Payment\Entity
     */
    protected $payment;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $params;

    protected $core;

    protected $event;

    /**
     * Param sshould have payment key with payment object during payment capture event
     *
     * @param string $mode
     * @param array  $params
     */
    public function __construct(string $mode, array $params)
    {
        parent::__construct($mode);

        $this->params   = collect($params);
        $this->event    = $this->params->get('event', self::PAYMENT_CAPTURE_EVENT);
        $this->payment  = $this->params->get('payment');
    }

    public function handle()
    {
        parent::handle();

        $handler = "handle" . Str::studly(Str::lower($this->event));

        $context = [
            'mode'  => $this->mode,
            'event' => $this->event
        ];

        if (! method_exists($this, $handler))
        {
            $this->trace->info(TraceCode::PAYMENT_LINK_POST_PROCESSOR_INVALID_EVENT, $context);
            return;
        }

        $this->trace->info(TraceCode::PAYMENT_LINK_POST_PROCESSOR_START, $context);

        $this->$handler();

        $this->trace->info(TraceCode::PAYMENT_LINK_POST_PROCESSOR_COMPLETED, $context);
    }

    protected function handlePaymentCaptureEvent()
    {
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
        catch (\Throwable $e)
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
