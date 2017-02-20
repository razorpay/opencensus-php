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
}
