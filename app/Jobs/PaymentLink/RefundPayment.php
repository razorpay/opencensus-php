<?php

namespace RZP\Jobs\PaymentLink;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

/**
 * Represents asynchronous Job for refunding payments of a Payment Link.
 * This is edge case is to be minimized or removed later with better semaphore approach.
 */
class RefundPayment extends Job
{
    /**
     * Payment entity.
     *
     * @var Payment\Entity
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
        $processor   = new PaymentProcessor($this->payment->merchant);

        $this->trace->info(
            TraceCode::PAYMENT_LINK_PAYMENT_ASYNC_REFUND_REQUEST,
            [
                E::PAYMENT      => $this->payment->toArrayPublic(),
                E::PAYMENT_LINK => $paymentLink->toArrayPublic(),
            ]);

        try
        {
            if ($this->payment->isAuthorized() === true)
            {
                $refund = $processor->refundAuthorizedPayment($this->payment);
            }
            else if ($this->payment->isCaptured() === true)
            {
                $refund = $processor->refundCapturedPayment($this->payment);
            }
            //
            // If following case happens that means we intended to refund earlier but by the time queue received the
            // job another operation has been done on payment and refund cannot be made now.
            // We just trace as critical and ignore for now.
            //
            else
            {
                throw new LogicException('Payment is in an invalid state for refund');
            }

            $this->trace->info(
                TraceCode::PAYMENT_LINK_PAYMENT_ASYNC_REFUND_HANDLED,
                [
                    E::PAYMENT      => $this->payment->toArrayPublic(),
                    E::PAYMENT_LINK => $paymentLink->toArrayPublic(),
                    E::REFUND       => $refund->toArrayPublic(),
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::PAYMENT_LINK_PAYMENT_ASYNC_REFUND_ERROR,
                [
                    E::PAYMENT      => $this->payment->toArrayPublic(),
                    E::PAYMENT_LINK => $paymentLink->toArrayPublic(),
                ]);
        }
    }

    /**
     * This method is used in tests for assertions
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->payment->getId();
    }
}
