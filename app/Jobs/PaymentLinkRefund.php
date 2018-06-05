<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

/**
 * Represents asynchronous Job for refunding payments of a Payment Link.
 */
class PaymentLinkRefund extends Job
{
    /**
     * Payment entity id.
     *
     * @var string
     */
    protected $paymentId;

    /**
     * Additional parameters from request or query.
     *
     * @var array
     */
    protected $params;

    public function __construct(string $mode, string $paymentId, array $params = [])
    {
        parent::__construct($mode);

        $this->paymentId    = $paymentId;
        $this->params       = $params;
    }

    public function handle()
    {
        parent::handle();

        $payment = $this->repoManager->payment->findOrFail($this->paymentId);

        $paymentLink = $payment->paymentLink;

        $processor = new PaymentProcessor($payment->merchant);

        try
        {
            $refundId = null;

            if ($payment->isAuthorized() === true)
            {
                $refund = $processor->refundAuthorizedPayment($payment);

                $refundId = $refund->getId();
            }
            else if ($payment->isCaptured() === true)
            {
                $refund = $processor->refundPaymentViaMerchant($payment->getPublicId());

                $refundId = $refund->getId();
            }

            $this->trace->debug(
                TraceCode::PAYMENT_LINK_PAYMENT_REFUNDED,
                [
                    'payment_id'        => $payment->getId(),
                    'payment_link_id'   => $paymentLink->getId(),
                    'refund_id'         => $refundId,
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::PAYMENT_LINK_PAYMENT_REFUND_EXCEPTION,
                [
                    'payment_id'        => $payment->getId(),
                    'payment_link_id'   => $paymentLink->getId(),
                ]);
        }
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }
}
