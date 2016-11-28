<?php

namespace RZP\Models\Wallet;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function getBalance(string $id)
    {
        $wallet = $this->core->fetchByCustomerId($id);

        return $wallet->toArrayPublic();
    }

    public function getTransactionStatement($id)
    {

    }

    public function loadMoney($customerId, array $input)
    {
        $wallet = $this->core->fetchOrCreate($customerId, $input);

        return $this->core->credit($wallet, $input['amount']);
    }

    public function sendMoney($id, array $input)
    {

    }

    public function refund($id, array $input)
    {

    }
}
