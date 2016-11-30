<?php

namespace RZP\Models\Customer\Transactions;

use RZP\Models\Base;
use RZP\Models\Wallet as CustomerBalance;

class Core extends Base\Core
{

    public function createFromCustomerDebit($payment, $txn)
    {
        $customerTxn = new Entity;

        $amount = $txn->getAmount();

        $customer = $payment->customer;

        $balance = $this->repo->wallets
                        ->findByCustomerIdAndMerchant($customer->getPublicId(), $payment->merchant)
                        ->getBalance();

        $txnData = [
            Entity::ENTITY_ID           => $customer->getId(),
            Entity::ENTITY_TYPE         => Type::CUSTOMER,
            Entity::STATUS              => 'transfered', // ? todo
            Entity::AMOUNT              => $amount,
            Entity::CURRENCY            => 'INR',
            Entity::CREDIT              => 0,
            Entity::DEBIT               => $amount,
            Entity::BALANCE             => $balance,
            Entity::DESCRIPTION         => 'NA', // ? todo
        ];

        $customerTxn->fillAndGenerateId($txnData);

        (new CustomerBalance\Service)->debit($payment->customer, $amount);

        return $customerTxn;
    }

}
