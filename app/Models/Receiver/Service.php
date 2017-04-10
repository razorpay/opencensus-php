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

        $bankAccount = $this->core->addVirtualBankAccountForCustomer($customer);

        return $bankAccount->toArrayPublic();
    }

    public function createStandingBankAccount()
    {
        $bankAccount = $this->core->addStandingBankAccount();

        return $bankAccount->toArrayPublic();
    }
}
