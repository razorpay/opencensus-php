<?php

namespace RZP\Models\Transfer\Payment;

use RZP\Models\Base;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createOrFetch($payment, $saveEntity=true)
    {
        $isAmountTransferredExpEnabled = (new Transfer\Service())->isAmountTransferredRearchExpEnabled(
            $payment->getId(), $payment->merchant->getId());

        if ($isAmountTransferredExpEnabled)
        {
            $transferPayments = $this->repo->transfer_payment->getTransferPaymentIncludingExternal($payment->getId());
        }
        else
        {
            $transferPayments = $this->repo->transfer_payment->getTransferPayment($payment->getId());
        }

        if  ($transferPayments->count() > 0)
        {
            return $transferPayments[0];
        }

        $input = [
            Entity::PAYMENT_ID             => $payment->getId(),
            Entity::AMOUNT                 => $payment->getAmount(),
            Entity::AMOUNT_TRANSFERRED     => 0,
        ];

        $this->trace->info(
            TraceCode::TRANSFER_PAYMENT_CREATE_REQUEST,
            [
                'input'    => $input,
            ]);

        $transferPayment = (new Entity)->build($input);

        if ($saveEntity === true)
        {
            $transferPayment->saveOrFail();
        }

        return $transferPayment;
    }
}
