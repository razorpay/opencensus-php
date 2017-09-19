<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Transaction;
use RZP\Constants\Entity as E;

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

        $entity = E::getEntityClass('payment');
        $payment = $entity::where('transfer_id', $reversal->getEntityId())->first();

        $this->fixtures->create(
            'refund:from_transfer_payment',
            [
                'payment'       => $payment,
                'created_at'    => $reversal->getCreatedAt(),
            ]);

        $txn = $this->createTransactionOnReversal($reversal);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $reversal->getCreatedAt());

        $txn->saveOrFail();

        $reversal->saveOrFail();

        return $reversal;
    }

}
