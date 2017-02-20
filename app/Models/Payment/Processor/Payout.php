<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payout\Core as PayoutCore;

trait Payout
{
    public function payout(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_PAYOUT_REQUEST,
            [
                'payment_id' => $id,
                'input'      => $input,
            ]
        );

        $payment = $this->retrieve($id);

        $this->validatePaymentForPayout($payment);

        return $this->mutex->acquireAndRelease($payment->getId(), function() use ($input, $payment)
        {
            $this->validateAndSetAmount($payment, $input);

            $payout = (new PayoutCore)->paymentPayout($input, $payment, $this->merchant);

            $this->updatePaymentAmountPaidout($payment, $payout->getAmount());

            return $payout;
        });
    }

    /**
     * Update the amount_paidout field in the Payment entity
     *
     * @param  Payment\Entity $payment
     * @param  int            $amount
     * @return null
     */
    protected function updatePaymentAmountPaidout(Payment\Entity $payment, int $amount)
    {
        $this->trace->info(
            TraceCode::PAYMENT_UPDATE_AMOUNT_PAIDOUT,
            [
                'payment_id' => $payment->getId(),
                'amount'     => $amount,
            ]
        );

        $payment->payoutAmount($amount);

        $this->repo->saveOrFail($payment);
    }

    protected function validatePaymentForPayout(Payment\Entity $payment)
    {
        if ($payment->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }

        if ($payment->transaction->isSettled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_BEFORE_SETTLEMENT);
        }
    }

    protected function validateAndSetAmount(Payment\Entity $payment, array & $input)
    {
        $payoutAmountPending = $payment->getAmount() - $payment->getAmountPaidout();

        if ($payoutAmountPending === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FULLY_PAIDOUT);
        }

        if (isset($input['amount']) === true)
        {
            if ((int) $input['amount'] > $payoutAmountPending)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_PENDING);
            }
        }

        $input['amount'] = $input['amount'] ?? $payoutAmountPending;
    }

}
