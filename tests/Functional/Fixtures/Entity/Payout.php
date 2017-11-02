<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Payout extends Base
{
    use TransactionTrait;

    public function create(array $attributes = [])
    {
        $defaultValues = [
            'customer_id'       => '100000customer',
            'destination_id'    => '1000000lcustba',
            'destination_type'  => 'bank_account',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $payout = parent::create($attributes);

        $txn = $this->createTransactionFromPayout($payout);

        $txn->setAttribute(\RZP\Models\Transaction\Entity::SETTLED_AT, $payout->getCreatedAt());

        $txn->saveOrFail();

        $payout->setFees($txn->getFee());

        $payout->setTax($txn->getTax());

        $payout->saveOrFail();

        return $payout;
    }
}
