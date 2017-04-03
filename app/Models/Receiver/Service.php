<?php

namespace RZP\Models\Receiver;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function createCustomerBankAccount($id)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $ba = $this->core->addVirtualBankAccountForCustomer($customer);

        return $ba->toArrayPublic();
    }

    public function createStandingBankAccount()
    {
        $ba = $this->core->addStandingBankAccount();

        return $ba->toArrayPublic();
    }
}
