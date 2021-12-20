<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\PaymentLink;
use Illuminate\Support\Str;
use RZP\Models\Merchant;

/**
 * - Asynchronously update payment page, generate receipt etc after a successful payment
 */
class PaymentPageProcessor extends Job
{
    const RETRY_DELAY           = 60;
    const MAX_RETRY_ATTEMPTS    = 5;

    const PAYMENT_CAPTURE_EVENT     = 'PAYMENT_CAPTURE_EVENT';
    const REFUND_PROCESSED_EVENT    = 'REFUND_PROCESSED_EVENT';
    const PAYMENT_HANDLE_CREATION   = 'PAYMENT_HANDLE_CREATION';

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

    /**
     * @var \RZP\Models\Merchant\Entity
     */
    protected $merchant;

    protected $core;

    protected $event;

    protected $service;

    /**
     * Params should have payment key with payment object during payment capture event
     *
     * Params should have refund_id key with a string value which should refer to a valid refund id during
     * refund processed event
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
        $this->merchant = $this->params->get('merchant');
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

    protected function handleRefundProcessedEvent()
    {
        $this->core = new PaymentLink\Core;

        $refund = $this
            ->repoManager
            ->refund
            ->findByIdAndMerchant($this->params['refund_id'], $this->merchant);

        $context = [
            'refund_id'         => $refund->getId(),
            'refund_status'     => $refund->getStatus(),
            "refund"            => $refund->toArrayPublic(),
            'payment_id'        => $refund->payment->getId(),
            'payment_status'    => $refund->payment->getStatus(),
        ];

        try
        {
            $this->trace->info(TraceCode::PAYMENT_LINK_REFUND_PROCESS_QUEUE, $context);

            $this->core->postPaymentRefundUpdatePaymentPage($refund);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, null, $context);
        }

        $this->delete();
    }

    protected function handlePaymentHandleCreation()
    {
        $this->service = new PaymentLink\Service;

        try
        {
            $merchantId = $this->params->get('merchant_id');

            $merchant = $this->repoManager->merchant->findByPublicId($merchantId);

            $this->trace->info(TraceCode::PAYMENT_HANDLE_CREATION_QUEUE_START);

            $this->setMerchant($merchant);

            $paymentHandle = $this->service->createPaymentHandle();

            $context = [
                PaymentLink\Entity::ID      => $paymentHandle[PaymentLink\Entity::ID],
                PaymentLink\Entity::TITLE   => $paymentHandle[PaymentLink\Entity::TITLE],
                PaymentLink\Entity::SLUG    => $paymentHandle[PaymentLink\Entity::SLUG],
                PaymentLink\Entity::URL     => $paymentHandle[PaymentLink\Entity::URL],
            ];

            $this->trace->info(TraceCode::PAYMENT_HANDLE_CREATION_QUEUE_COMPLETED, $context);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e, null, null, [
                'params'    => $this->params->toArray(),
            ]);
        }

        $this->delete();
    }

    protected function setMerchant(Merchant\Entity $merchant)
    {
        $this->app = App::getFacadeRoot();

        $this->app['basicauth']->setMerchant($merchant);
    }
}
