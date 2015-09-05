<?php

namespace Models\Merchant\Methods;

use Models\Base;
use EE\Exception;
use Models\Payment;
use Models\Merchant;
use Models\Merchant\Methods;
use Models\Payment\Processor\Netbanking;
use Models\Terminal;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository;
    }

    public function setPaymentMethods($merchant, $input)
    {
        $methods = $this->repo->getMerchantBanks($merchant->getId());

        $methods->setMethods($input);

        $this->repo->saveOrFail($methods);

        return $methods->toArray();
    }

    public function getMerchantBanks($merchant)
    {
        $banks = $this->repo->getMerchantBanks($merchant->getId());

        // $billdesk = (new Terminal\Repository)->getByMerchantIdAndGateway(
        //                                         $merchant->getId(), 'billdesk');
        // if ($billdesk !== null)
        // {
        //     $supportedBanks = Netbanking::getAllBanks();
        // }
        // else
        // {
        //     $supportedBanks = Payment\Processor\Netbanking::getPaytmSupportedBanks();
        // }

//        $supportedBanks = Netbanking::getBilldeskSupportedBanks();
        $supportedBanks = array_merge(Netbanking::getBilldeskSupportedBanks(),Netbanking::getSbiepaySupportedBanks());

        $banks->setBanks($supportedBanks);

        return $banks;
    }

    public function getMerchantBanksArray($merchant)
    {
        $banks = $this->getMerchantBanks($merchant);

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
            $banks = new Methods\Entity;
            $banks->merchant()->associate($merchant);
        }

        return $this->setPaymentBanks($banks, $input);
    }

    public function setAllPaymentBanks($merchant)
    {
        $input = [
            'banks' => \Models\Payment\Processor\Netbanking::getAllBanks()
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

        $disabled = Payment\Processor\Netbanking::getDisabledBanks($enabled);

        $data = array(
            'enabled' => $this->getBankNames($enabled),
            'disabled' => $this->getBankNames($disabled));

        return $data;
    }
}
