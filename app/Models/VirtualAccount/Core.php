<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\Base;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Customer\Entity as Customer;

class Core extends Base\Core
{
    public function create(array $input, Merchant $merchant, Customer $customer = null)
    {
        $virtualAccount = (new Entity)->build($input);

        if ($customer !== null)
        {
            $virtualAccount->customer()->associate($customer);
        }

        $virtualAccount->merchant()->associate($merchant);

        $this->repo->saveOrFail($virtualAccount);

        return $virtualAccount;
    }
}
