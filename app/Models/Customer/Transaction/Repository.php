<?php

namespace RZP\Models\Customer\Transaction;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Entity as E;

class Repository extends Base\Repository
{
    protected $entity = 'customer_transaction';

    protected $entityFetchParamRules = [
        Entity::CUSTOMER_ID   => 'sometimes|string|size:14'
    ];

    protected $appFetchParamRules = [
        Entity::CUSTOMER_ID   => 'sometimes|string|size:14',
        Entity::MERCHANT_ID   => 'sometimes|string|size:14',
    ];

    public function findByPaymentIdAndAmountForVerify(string $paymentId, int $amount, string $merchantId)
    {
        Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        $customerTxn = $this->newQuery()
                            ->where(Entity::TYPE, Type::TRANSFER)
                            ->where(Entity::ENTITY_TYPE, E::PAYMENT)
                            ->where(Entity::ENTITY_ID, $paymentId)
                            ->where(Entity::AMOUNT, $amount)
                            ->where(Entity::DEBIT, $amount)
                            ->where(Entity::STATUS, 'complete')
                            ->merchantId($merchantId)
                            ->first();

        return $customerTxn;
    }
}
