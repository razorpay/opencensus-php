<?php

namespace RZP\Models\Customer\Transaction;

use Carbon\Carbon;
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

        $customerTxn = $this->createEntityForType(
            'debit', $payment->merchant, $amount, $payment->customer);

        $balance = (new Customer\Balance\Service)->debit($payment->customer, $amount);

        $customerTxn->setBalance($balance->getBalance());

        return $customerTxn;
    }

    /**
     * Create customer_transaction on payment capture+transfer.
     *
     * @param  Payment\Entity   $payment
     * @param  int              $amount
     * @param  Customer\Entity  $customer
     * @return Entity
     */
    public function createFromCustomerCredit($payment, int $amount, $customer)
    {
        $customerTxn = $this->createEntityForType('credit', $payment->merchant, $amount, $customer);

        $balance = $this->repo->customer_balance
                        ->findByCustomerIdAndMerchant(
                            $customer->getPublicId(), $payment->merchant);

        $customerTxn->setBalance($balance->getBalance());

        return $customerTxn;
    }

    /**
     * Create entry for a refund transaction
     *
     * @param  string $customerId
     * @param  int    $amount
     * @return Entity
     */
    public function createFromCustomerRefund($customerId, int $amount)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);

        $customerTxn = $this->createEntityForType('credit', $this->merchant, $amount, $customer);

        $customerTxn->setType(Type::REFUND);

        $balance = (new Customer\Balance\Core)->refund($customer->getPublicId(), $amount);

        $customerTxn->setBalance($balance->getBalance());

        return $customerTxn;
    }

    protected function createEntityForType(string $type, $merchant, int $amount, $customer)
    {
        $customerTxn = new Entity;

        $txnData = [
            Entity::ENTITY_ID           => $customer->getId(),
            Entity::ENTITY_TYPE         => 'customer',
            Entity::TYPE                => Type::TRANSFER,
            Entity::STATUS              => 'transferred', // ? todo
            Entity::AMOUNT              => $amount,
            Entity::CURRENCY            => 'INR',
            Entity::DESCRIPTION         => 'NA', // ? todo
        ];

        if ($type === Entity::DEBIT)
        {
            $customerTxn->setDebit($amount);

            $customerTxn->setCredit(0);
        }
        else if ($type === Entity::CREDIT)
        {
            $customerTxn->setCredit($amount);

            $customerTxn->setDebit(0);
        }
        else
        {
            assert (false);
        }

        $customerTxn->customer()->associate($customer);

        $customerTxn->merchant()->associate($merchant);

        $customerTxn->fillAndGenerateId($txnData);

        return $customerTxn;
    }

    public function getLastTransactionTime(Customer\Balance\Entity $balance)
    {
        $lastTxn = $this->repo
                        ->customer_transaction
                        ->fetchLastCreditTransaction(
                            $balance->getCustomerId(), $this->merchant->getId());

        if ($lastTxn === NULL)
        {
            return NULL;
        }

        return Carbon::createFromTimestamp($lastTxn->getCreatedAt(), 'Asia/Kolkata');
    }
}
