<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Payout\Validator as PayoutValidator;

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

        $payment->getValidator()->validateForPayout($this->mode);

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

    protected function validateAndSetAmount(Payment\Entity $payment, & $input)
    {
        (new PayoutValidator)->validatePayoutAmount($input, $payment);

        $paymentPayoutPending = $payment->getAmount() - $payment->getAmountPaidout();

        $input['amount'] = $input['amount'] ?? $paymentPayoutPending;
    }
}
