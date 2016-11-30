<?php

namespace RZP\Models\Customer\Transactions;

use RZP\Models\Base;
use RZP\Models\Customer;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function getStatement($customerId, $input)
    {
        $entities = $this->repo->customer_transactions
                    ->fetchCustomerStatement($input, $customerId, $this->merchant->getId());

        return $entities->toArrayPublic();
    }
}
