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

    public function getBalance(string $customerId) : array
    {
        $wallet = $this->repo->wallets->findByCustomerIdAndMerchant($customerId, $this->merchant);

        return $wallet->toArrayPublic();
    }

    public function getTransactionStatement($customerId)
    {

    }

    public function loadMoney($customerId, int $amount) : array
    {
        $wallet = $this->core->fetchOrCreate($customerId);

        return $this->core->credit($wallet, $amount)->toArrayPublic();
    }

    public function sendMoney($customerId, array $input)
    {

    }

    public function debit($customerId, array $input)
    {

    }

    public function refund($customerId, array $input)
    {

    }
}
