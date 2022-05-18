<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Dispute\Reason\Entity as Reason;
use RZP\Models\Transaction\Entity as Transaction;

class Dispute extends Base
{
    use TransactionTrait;

    public function create(array $attributes = [])
    {
        $paymentAttributes = ['disputed' => 1];
        if(isset($attributes['test']) && $attributes['test'] === 'nium')
        {
            $paymentAttributes['merchant_id'] = $attributes['merchant_id'];
            $paymentAttributes['amount'] = $attributes['amount'];
            unset($attributes['test']);
        }

        $payment = $this->fixtures->create('payment:captured', $paymentAttributes);

        $reason = $this->fixtures->create('dispute_reason');

        $defaultValues = $this->getDefaultAttributes($payment, $reason);

        $attributes = array_merge($defaultValues, $attributes);

        if (empty($attributes['deduct_at_onset']) === false)
        {
            $attributes['amount_deducted'] = $attributes['amount'];
        }

        $dispute = $this->createEntity('dispute', $attributes);

        $payment = $dispute->payment;

        $flag = ($dispute->isClosed() === true) ? 0 : 1;

        $this->fixtures->edit('payment', $payment->getId(), [Payment::DISPUTED => $flag]);

        // Create a transaction only when there's a deduction required
        if ($dispute->getDeductAtOnset() === true)
        {
            $txn = $this->fixtures->create(
                'transaction',
                [
                    'merchant_id' => $dispute->getMerchantId(),
                    'amount'      => $dispute->getAmount(),
                    'debit'       => $dispute->getAmount(),
                    'credit'      => 0
                ]
            );

            $txn->setAttribute(Transaction::SETTLED_AT, $dispute->getCreatedAt());
            $txn->setAttribute(Transaction::TYPE, 'adjustment');

            $adj = $this->fixtures->create(
                'adjustment',
                [
                    'transaction_id' => $txn->getId(),
                    'entity_type'    => 'dispute',
                    'entity_id'      => $dispute->getId(),
                    'amount'         => 0 - $dispute->getAmount(),
                ]);

            $txn->setAttribute(Transaction::ENTITY_ID, $adj->getId());

            $txn->saveOrFail();
        }

        return $dispute;
    }

    protected function getDefaultAttributes(
        Payment $payment,
        Reason $reason): array
    {
        return [
            'amount'           => $payment->getAmount(),
            'currency'         => $payment->getCurrency(),
            'payment_id'       => $payment->getId(),
            'merchant_id'      => $payment->getMerchantId(),
            'reason_id'        => $reason->getId(),
        ];
    }
}
