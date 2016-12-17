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

    public function getBalance(string $customerId) : array
    {
        $balance = $this->repo
                        ->customer_balance
                        ->findByIdAndMerchant($customerId, $this->merchant);

        return $balance->toArrayPublic();
    }

    public function credit(Customer\Entity $customer, int $amount)
    {
        $balance = $this->core->fetchOrCreate($customer);

        return $this->core->credit($balance, $amount);
    }

    public function debit(string $customerId, int $amount)
    {
        $balance = $this->repo
                        ->customer_balance
                        ->getCustomerBalanceLockForUpdate($customerId);

        return $this->core->debit($balance, $amount);
    }

    public function refund(Customer\Entity $customer, int $amount)
    {
        return $this->core->refund($customer->getPublicId(), $amount);
    }
}
