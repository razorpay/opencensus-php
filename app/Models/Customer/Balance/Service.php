<?php

namespace RZP\Models\Customer\Balance;

use RZP\Models\Base;
use RZP\Models\Customer;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    /**
     * Fetch balance details for a customer wallet account
     *
     * @param  string $customerId
     * @return array
     */
    public function getBalance(string $customerId) : array
    {
        $balance = $this->repo
                        ->customer_balance
                        ->findByIdAndMerchant($customerId, $this->merchant);

        return $balance->toArrayPublic();
    }

    /**
     * Credit customer wallet account
     *
     * @param  Customer\Entity $customer
     * @param  int             $amount
     */
    public function credit(Customer\Entity $customer, int $amount)
    {
        $balance = $this->core->fetchOrCreate($customer, $this->merchant);

        return $this->core->credit($balance, $amount);
    }

    /**
     * Debit customer wallet account
     *
     * @param  string $customerId
     * @param  int    $amount
     */
    public function debit(string $customerId, int $amount)
    {
        $balance = $this->repo
                        ->customer_balance
                        ->getCustomerBalanceLockForUpdate($customerId);

        return $this->core->debit($balance, $amount);
    }

    /**
     * Refund an amount to customer wallet account
     *
     * @param  Customer\Entity $customer
     * @param  int             $amount
     */
    public function refund(Customer\Entity $customer, int $amount)
    {
        return $this->core->refund($customer->getPublicId(), $amount);
    }
}
