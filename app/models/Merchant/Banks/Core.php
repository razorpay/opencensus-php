<?php

namespace Models\Merchant\Banks;

use Models\Base;
use Models\Payment;
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

        $enabledBanks = Payment\Processor\NetBanking::getEnabledBanks();

        $banks->setBanks($enabledBanks);

        return $banks;
    }

    public function getMerchantBanksArray($merchant)
    {
        $banks = (new Banks\Core)->getMerchantBanks($merchant);

        if ($banks === null)
            return [];

        return $banks->toArrayWithBankNames();
    }

    public function getEnabledAndDisabledBanks($merchant)
    {
        $banks = $this->repo->getMerchantBanks($merchant->getId());

        return $this->getEnabledDisabledBanks($banks);
    }

    public function getBankNames($banks)
    {
        return \Models\Bank\Name::getNames($banks);
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

        return $this->getEnabledDisabledBanks($banks);
    }

    protected function getEnabledDisabledBanks($banks)
    {
        $enabled = [];

        if ($banks !== null)
        {
            $enabled = $banks->getBanks();
        }

        $disabled = Payment\Processor\NetBanking::getDisabledBanks($enabled);

        $data = array(
            'enabled' => $this->getBankNames($enabled),
            'disabled' => $this->getBankNames($disabled));

        return $data;
    }
}
