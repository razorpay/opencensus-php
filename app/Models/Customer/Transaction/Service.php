<?php

namespace RZP\Models\Customer\Transaction;

use RZP\Models\Base;
use RZP\Models\Customer;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function createForRefund(array $input) : string
    {
        $customerTxn = $this->core
                            ->createForCustomerRefund($input, $this->merchant);

        return $customerTxn->getId();
    }

    public function createForDebit(array $input) : string
    {
        $customerTxn = $this->core
                            ->createForCustomerDebit($input, $this->merchant);

        return $customerTxn->getId();
    }
}
