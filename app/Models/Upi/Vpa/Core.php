<?php

namespace RZP\Models\Upi\Vpa;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;

class Core extends Base\Core
{
    public function createVpa($input, $customer, $bankAccount)
    {
        $vpa = (new Entity)->build($input);

        $vpa->generateId();

        $vpa->customer()->associate($customer);

        $vpa->bankAccount()->associate($bankAccount);

        $vpa->saveOrFail();

        return $vpa;
    }

    public function editVpa($vpa, $input)
    {
        $vpa->edit($input);

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }
}
