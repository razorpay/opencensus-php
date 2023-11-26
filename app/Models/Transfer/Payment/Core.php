<?php

namespace RZP\Models\Transfer\Payment;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createOrFetch($payment)
    {
        $transferPayments = $this->repo->transfer_payment->getTransferPayment($payment->getId());

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

        $transferPayment->saveOrFail();

        return $transferPayment;
    }
}
