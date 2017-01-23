<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;

class Service extends Merchant\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function fetch($id)
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        return $account->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $accounts = $this->repo->account->fetch($input, $this->merchant->getId());

        return $accounts->toArrayPublic();
    }

    public function create(array $input)
    {
        $merchant = $this->core->createAccount($input, $this->merchant);

        return $merchant->toArrayPublic();
    }

    public function edit(string $id, array $input)
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        $this->setSettlementScheduleIdIfNeeded($account, $input);

        $account = $this->core->edit($account, $input);

        return $account->toArrayPublic();
    }
}
