<?php

namespace RZP\Models\Dispute;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input,
        Payment\Entity $payment,
        Reason\Entity $reason): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_CREATE_REQUEST,
            array_merge($input, ['payment_id' => $payment->getId()])
        );

        (new Validator)->validatePaymentForDispute($input, $payment);

        $dispute = (new Entity)->build($input);

        $this->setRelationsAndDerivedAttributes($dispute, $payment, $reason);

        $payment->setDisputed(true);

        $dispute = $this->repo->transaction(function() use ($dispute)
        {
            $this->repo->payment->saveOrFail($dispute->payment);

            $this->repo->dispute->saveOrFail($dispute);

            return $dispute;
        });

        // TODO: Send email to merchant

        return $dispute;
    }

    protected function setRelationsAndDerivedAttributes(
        Entity $dispute,
        Payment\Entity $payment,
        Reason\Entity $reason): Entity
    {
        $merchant = $payment->merchant;

        $dispute->setCurrency($payment->getCurrency());

        $dispute->setReasonCode($reason->getCode());

        $dispute->setReasonDescription($reason->getDescription());

        $dispute->payment()->associate($payment);

        $dispute->merchant()->associate($merchant);

        $dispute->reason()->associate($reason);

        return $dispute;
    }
}
