<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($input, $merchant, $customer = null)
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
