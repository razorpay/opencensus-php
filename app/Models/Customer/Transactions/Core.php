<?php

namespace RZP\Models\Customer\Transactions;

use RZP\Models\Base;
use RZP\Models\Customer;

class Core extends Base\Core
{
    /**
     * Creates a customer_transaction record and a amount debit on the customer wallet balance
     * Called at payment authorize, for a flashwallet payment.
     *
     * @param  Payment\Entity   $payment
     * @return Customer\Transaction\Entity
     */
    public function createForCustomerDebit($payment)
    {
        $amount = $payment->getAmount();

        $customerTxn = $this->createEntityForType('debit', $payment, $amount, $payment->customer);

        $balance = (new Customer\Balance\Service)->debit($payment->customer, $amount);

        $customerTxn->setBalance($balance->getBalance());

        return $customerTxn;
    }

    public function createFromCustomerCredit($payment, int $amount, $customer)
    {
        $customerTxn = $this->createEntityForType('credit', $payment, $amount, $customer);

        $balance = $this->repo->customer_balance
                        ->findByCustomerIdAndMerchant($customer->getPublicId(), $payment->merchant);

        $customerTxn->setBalance($balance->getBalance());

        return $customerTxn;
    }

    protected function createEntityForType(string $type, $payment, int $amount, $customer)
    {
        $customerTxn = new Entity;

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
