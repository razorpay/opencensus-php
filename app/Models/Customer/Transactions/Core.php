<?php

namespace RZP\Models\Customer\Transactions;

use RZP\Models\Base;
use RZP\Models\Wallet as CustomerBalance;

class Core extends Base\Core
{

    public function createFromCustomerDebit($payment, $txn)
    {
        $customerTxn = $this->createEntityForType('debit', $payment, $txn);

        $balance = (new CustomerBalance\Service)->debit($payment->customer, $txn->getAmount());

        // Lock for get balance

        $customerTxn->setBalance($balance->getBalance());

        return $customerTxn;
    }

    public function createFromCustomerCredit($payment, $txn)
    {
        $customerTxn = $this->createEntityForType('credit', $payment, $txn);

        $balance = (new CustomerBalance\Service)->credit($payment->customer, $txn->getAmount());

        // Lock for get balance

        $customerTxn->setBalance($balance->getBalance());

        return $customerTxn;
    }

    protected function createEntityForType(string $type, $payment, $txn)
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
            Entity::DESCRIPTION         => 'NA', // ? todo
        ];

        if ($type === 'debit')
        {
            $customerTxn->setDebit($amount);
        }
        else
        {
            $customerTxn->setCredit($amount);
        }

        $customerTxn->fillAndGenerateId($txnData);

        return $customerTxn;
    }

}
