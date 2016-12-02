<?php

namespace RZP\Models\Customer\Transactions;

use RZP\Models\Base;
use RZP\Models\Customer;

class Core extends Base\Core
{

    public function createFromCustomerDebit($payment, $txn)
    {
        $customerTxn = $this->createEntityForType('debit', $payment, $txn, $payment->customer);

        $balance = (new Customer\Balance\Service)->debit($payment->customer, $txn->getAmount());

        // Lock for get balance

        $customerTxn->setBalance($balance->getBalance());

        return $customerTxn;
    }

    public function createFromCustomerCredit($payment, $txn, $customer)
    {
        $customerTxn = $this->createEntityForType('credit', $payment, $txn, $customer);

        return $customerTxn;
    }

    protected function createEntityForType(string $type, $payment, $txn, $customer)
    {
        $customerTxn = new Entity;

        $amount = $txn->getAmount();

        $balance = $this->repo->customer_balance
                        ->findByCustomerIdAndMerchant($customer->getPublicId(), $payment->merchant)
                        ->getBalance();

        $txnData = [
            Entity::ENTITY_ID           => $customer->getId(),
            Entity::ENTITY_TYPE         => Type::CUSTOMER,
            Entity::STATUS              => 'transfered', // ? todo
            Entity::AMOUNT              => $amount,
            Entity::CURRENCY            => 'INR',
            Entity::DESCRIPTION         => 'NA', // ? todo
        ];

        if ($type === 'debit')
        {
            $customerTxn->setDebit($amount);

            $customerTxn->setCredit(0);
        }
        else
        {
            $customerTxn->setCredit($amount);

            $customerTxn->setDebit(0);
        }

        $customerTxn->customer()->associate($customer);

        $customerTxn->merchant()->associate($payment->merchant);

        $customerTxn->fillAndGenerateId($txnData);

        return $customerTxn;
    }

}
