<?php

namespace Models\Merchant\Methods;

use Models\Base;
use Models\Merchant;
use EE\Exception;
use EE\Error\ErrorCode;

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