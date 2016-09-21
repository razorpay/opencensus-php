<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Transaction;

class Payout extends Base
{
    use TransactionTrait;

    public function create(array $attributes = array())
    {
        $defaultValues = array(
            'customer_id' => '100000customer',
            'destination' => '1000000lcustba',
        );

        $attributes = array_merge($defaultValues, $attributes);

        $payout = parent::create($attributes);

        $txn = $this->createTransactionFromPayout($payout);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $payout->getCreatedAt());

        $txn->saveOrFail();

        $payout->saveOrFail();

        return $payout;
    }
}