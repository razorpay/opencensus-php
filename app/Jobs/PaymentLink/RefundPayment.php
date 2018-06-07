<?php

namespace RZP\Jobs\PaymentLink;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception\LogicException;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

/**
 * Represents asynchronous Job for refunding payments of a Payment Link.
 */
class RefundPayment extends Job
{
    /**
     * Payment entity.
     *
     * @var string
     */
    protected $payment;

    public function __construct(string $mode, Payment\Entity $payment)
    {
        parent::__construct($mode);

        $this->payment = $payment;
    }

    public function handle()
    {
        parent::handle();

        $paymentLink = $this->payment->paymentLink;

        $this->trace->info(
            TraceCode::PAYMENT_LINK_PAYMENT_ASYNC_REFUND_HANDLED,
            [
                'payment'       => $this->payment->toArrayPublic(),
                'payment_link'  => $paymentLink->toArrayPublic(),
            ]);

        $processor = new PaymentProcessor($this->payment->merchant);

        try
        {
            $refund = null;

            if ($this->payment->isAuthorized() === true)
            {
                $refund = $processor->refundAuthorizedPayment($this->payment);
            }
            else if ($this->payment->isCaptured() === true)
            {
                $refund = $processor->refundPaymentViaMerchant($this->payment->getPublicId());
            }
            else
            {
                throw new LogicException(
                    'Payment is in an invalid state for refund',
                    null,
                    $this->payment->toArrayPublic());
            }

            $this->trace->info(
                TraceCode::PAYMENT_LINK_PAYMENT_ASYNC_REFUNDED,
                [
                    'payment'       => $this->payment->toArrayPublic(),
                    'payment_link'  => $paymentLink->toArrayPublic(),
                    'refund'        => $refund->toArrayPublic(),
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::PAYMENT_LINK_PAYMENT_ASYNC_REFUND_ERROR,
                [
                    'payment'       => $this->payment->toArrayPublic(),
                    'payment_link'  => $paymentLink->toArrayPublic(),
                ]);
        }
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }
}
