<?php

namespace RZP\Models\Dispute;

use Carbon\Carbon;
use RZP\Models\Admin\Action;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Reversal;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;

class Core extends Base\Core
{
    /**
     * @param Payment\Entity $payment
     * @param Reason\Entity  $reason
     * @param array          $input
     *
     * @return Entity
     */
    public function create(
        Payment\Entity $payment,
        Reason\Entity $reason,
        array $input): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_CREATE_REQUEST,
            [
                'input'      => $input,
                'payment_id' => $payment->getId()
            ]);

        (new Validator)->validatePaymentForDispute($input, $payment);

        $dispute = (new Entity)->build($input);

        $this->setRelationsAndDerivedAttributes($dispute, $payment, $reason);

        // entity id is required to create associated transaction
        $dispute->generateId();

        $this->app['workflow']
            ->setEntityAndId($dispute->getEntity(), $dispute->getId())
            ->handle((new \stdClass), $dispute);

        $dispute->setAuditAction(Action::CREATE_DISPUTE);

        $payment->setDisputed(true);

        $dispute = $this->repo->transaction(function() use ($dispute)
        {
            if ($dispute->getDeductAtOnset() === true)
            {
                $this->deductDisputedAmount($dispute);
            }

            $this->repo->saveOrFail($dispute->payment);

            $this->repo->saveOrFail($dispute);

            return $dispute;
        });

        // TODO: Send email to merchant

        return $dispute;
    }

    /**
     * @param Entity $dispute
     * @param array  $input
     *
     * @return Entity
     */
    public function update(Entity $dispute, array $input): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_EDIT_REQUEST,
            array_merge($input, [Entity::ID => $dispute->getId()])
        );

        $dispute->edit($input);

        $dispute->setAuditAction(Action::EDIT_DISPUTE);

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
        if ($dispute->isClosed() === false)
        {
            return;
        }

        $dispute->setResolvedAt(Carbon::now()->getTimestamp());

        $payment = $dispute->payment;

        $payment->setDisputed(false);

        $this->repo->saveOrFail($payment);

        if (($dispute->isLost() === true) and
            ($dispute->getAmountDeducted() === 0))
        {
            $this->deductDisputedAmount($dispute);
        }

        if ($this->shouldReverse($dispute) === true)
        {
            $this->createReversalAndUpdateDispute($dispute);
        }
    }

    protected function createReversalAndUpdateDispute(Entity $dispute)
    {
        $input = [
            Entity::CURRENCY    => $dispute->getCurrency(),
            Entity::AMOUNT      => $dispute->getAmountDeducted(),
        ];

        (new Reversal\Core)->createForDispute($dispute, $dispute->merchant, $input);

        $dispute->setAmountReversed($dispute->getAmountDeducted());
    }

    protected function shouldReverse(Entity $dispute): bool
    {
        return (($dispute->isWon() === true) and
                ($dispute->getAmountDeducted() > 0) and
                ($dispute->getAmountReversed() === 0));
    }

    protected function deductDisputedAmount(Entity $dispute)
    {
        $dispute->setAmountDeducted($dispute->getAmount());

        $txn = (new Transaction\Core)->createFromDispute($dispute);

        $this->repo->saveOrFail($txn);
    }
}
