<?php

namespace RZP\Models\Wallet;

use RZP\Models\Base;
use RZP\Models\Customer;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function getBalance(string $customerId) : array
    {
        $wallet = $this->repo->wallets
                       ->findByCustomerIdAndMerchant($customerId, $this->merchant);

        return $wallet->toArrayPublic();
    }

    public function credit(Customer\Entity $customer, int $amount)
    {
        $wallet = $this->core->fetchOrCreate($customer->getPublicId());

        return $this->core->credit($wallet, $amount)->toArrayPublic();
    }

    public function debit(Customer\Entity $customer, int $amount)
    {
        $wallet = $this->repo->wallets
                       ->findByCustomerIdAndMerchant($customer->getPublicId(), $this->merchant);

        return $this->core->debit($wallet, $amount);
    }

    public function sendMoney($customerId, array $input)
    {

    }

    public function refund($customerId, array $input)
    {

    }
}
