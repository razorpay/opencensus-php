<?php

namespace RZP\Tests\P2p\Service\Base;

class BankAccountHelper extends P2pHelper
{
    public function fetchBanks()
    {
        $this->validationJsonSchemaPath = 'bank_account/bank/fetch_all';

        $this->isCustomerInContext = false;

        $request = $this->request('banks');

        $this->isCustomerInContext = true;

        return $this->get($request);
    }
}
