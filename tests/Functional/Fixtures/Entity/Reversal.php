<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
use RZP\Models\Reversal\Core as ReversalCore;

class Reversal extends Base
{
    use TransactionTrait;

    public function create(array $attributes = [])
    {
        $defaultValues = [
            'amount'    => 200,
            'currency'  => 'INR',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $reversal = $this->build('reversal', $attributes);

        if ($attributes['entity_type'] !== 'payout') {
            $entity = E::getEntityClass('payment');
            $payment = $entity::where('transfer_id', $reversal->getEntityId())->first();

            $this->fixtures->create(
                'refund:from_transfer_payment',
                [
                    'amount' => $reversal->getAmount(),
                    'payment' => $payment,
                    'created_at' => $reversal->getCreatedAt(),
                ]);
        }

        $txn = null;
        if ($attributes['entity_type'] !== 'payout') {
            $txn = $this->createTransactionOnReversal($reversal);
        }
        else
        {
            $txn = $this->createTransactionOnPayoutReversal($reversal);
        }

        $txn->saveOrFail();

        $reversal->saveOrFail();

        return $reversal;
    }

    public function createPayoutReversal(array $attributes = [])
    {
        $defaultValues = [
            'amount'    => 200,
            'currency'  => 'INR',
            'channel'   => 'yesbank',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $reversal = $this->build('reversal', $attributes);

        if ($attributes['channel'] !== 'rbl')
        {
            $txn = $this->createTransactionOnPayoutReversal($reversal);

            $txn->saveOrFail();
        }

        $reversal->saveOrFail();

        return $reversal;
    }

    public function createReversalWithoutTransaction(array $attributes = [])
    {
        $defaultValues = [
            'currency' => 'INR',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $reversal = parent::create($attributes);

        return $reversal;
    }
}
