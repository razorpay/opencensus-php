<?php

namespace RZP\Models\Dispute;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(
        Payment\Entity $payment,
        Reason\Entity $reason,
        array $input): Entity
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
            $this->repo->saveOrFail($dispute->payment);

            $this->repo->saveOrFail($dispute);

            return $dispute;
        });

        // TODO: Send email to merchant

        return $dispute;
    }

    public function update(Entity $dispute, array $input): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_EDIT_REQUEST,
            array_merge($input, [Entity::ID => $dispute->getId()])
        );

        $dispute->edit($input);

        return $this->repo->transaction(function() use ($dispute)
        {
            $this->handleDisputeClosure($dispute);

            $this->repo->saveOrFail($dispute);

            return $dispute;
        });
    }

    protected function setRelationsAndDerivedAttributes(
        Entity $dispute,
        Payment\Entity $payment,
        Reason\Entity $reason)
    {
        $merchant = $payment->merchant;

        $dispute->setCurrency($payment->getCurrency());

        $dispute->setReasonCode($reason->getCode());

        $dispute->setReasonDescription($reason->getDescription());

        $dispute->payment()->associate($payment);

        $dispute->merchant()->associate($merchant);

        $dispute->reason()->associate($reason);
    }

    protected function handleDisputeClosure(Entity $dispute)
    {
        if ($dispute->isClosed() === true)
        {
            $dispute->setResolvedAt(Carbon::now()->getTimestamp());

            $payment = $dispute->payment;

            $payment->setDisputed(false);

            $this->repo->saveOrFail($payment);
        }
    }
}
