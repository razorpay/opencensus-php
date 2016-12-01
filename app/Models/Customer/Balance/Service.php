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
        $balance = $this->repo->customer_balance
                       ->findByCustomerIdAndMerchant($customerId, $this->merchant);

        return $balance->toArrayPublic();
    }

    public function credit(Customer\Entity $customer, int $amount)
    {
        $balance = $this->core->fetchOrCreate($customer->getPublicId());

        return $this->core->credit($balance, $amount)->toArrayPublic();
    }

    public function debit(Customer\Entity $customer, int $amount)
    {
        $balance = $this->repo->customer_balance
                       ->findByCustomerIdAndMerchant($customer->getPublicId(), $this->merchant);

        return $this->core->debit($balance, $amount);
    }

    public function sendMoney($customerId, array $input)
    {

    }

    public function refund($customerId, array $input)
    {

    }
}
