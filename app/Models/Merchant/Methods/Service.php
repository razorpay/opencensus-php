<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository();
    }

    public function getPaymentBanks()
    {
        $banks = $this->repo->getMerchantBanks($this->merchant->getId());

        if ($banks === null)
            return [];

        return $banks->toArrayWithBankNames();
    }
}
