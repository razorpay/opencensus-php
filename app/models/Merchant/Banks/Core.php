<?php

namespace Models\Merchant\Banks;

use Models\Base;
use Models\Merchant;
use Models\Merchant\Banks;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository;
    }

    public function getMerchantBanks($merchant)
    {
        $banks = $this->repo->getMerchantBanks($merchant->getId());

        return $banks;
    }

    public function setPaymentBanksForMerchant($merchant, $input)
    {
        $banks = $this->repo->getMerchantBanks($merchant->getId());

        if ($banks === null)
        {
            $banks = new Banks\Entity;
            $banks->merchant()->associate($merchant);
        }

        return $this->setPaymentBanks($banks, $input);
    }

    public function setAllPaymentBanks($merchant)
    {
        $input = [
            'banks' => \Models\Payment\Processor\NetBanking::getAllBanks()
        ];

        $banks = $this->setPaymentBanksForMerchant($merchant, $input);

        return $banks;
    }

    protected function setPaymentBanks($banks, $input)
    {
        (new Validator)->validateInput('addBanks', $input);

        $banks->setBanks($input['banks']);
        $this->repo->saveOrFail($banks);

        return $banks->toArray();
    }
}
