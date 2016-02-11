<?php

namespace Models\Merchant\Methods;

use Constants\Mode;
use EE\Exception;
use EE\Error\ErrorCode;
use Models\Bank\IFSC;
use Models\Base;
use Models\Payment;
use Models\Merchant;
use Models\Merchant\Methods;
use Models\Pricing;
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
        $methods = $this->repo->getMerchantMethods($merchant->getId());

        if ($methods === null)
        {
            $methods = $this->setDefaultMethods($merchant);
        }

        $methods->setMethods($input);

        $this->checkPricing($merchant, $methods);

        $this->repo->saveOrFail($methods);

        return $methods->toArray();
    }

    protected function checkPricing($merchant, $methods)
    {
        $pricingCore = new Pricing\Core;

        if (($methods->isAmexEnabled()) and
            ($pricingCore->checkPricingForAmex($merchant) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_RULE_FOR_AMEX_NOT_PRESENT);
        }

        if (($methods->isAnyWalletEnabled()) and
            ($pricingCore->hasWalletPricing($merchant) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Wallet pricing not present for merchant');
        }

        if (($methods->isEmiEnabled()) and
            ($pricingCore->hasEmiPricing($merchant) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Emi pricing not present for merchant');
        }
    }

    public function getMethods($merchant)
    {
        $methods = $this->repo->getMerchantMethods($merchant->getId());

        $supportedBanks = Netbanking::getSupportedBanks($this->mode);

        $methods->setBanks($supportedBanks);

        return $methods;
    }

    public function getEnabledAndDisabledBanks($merchant)
    {
        $banks = $this->repo->getMerchantMethods($merchant->getId());

        return $this->getEnabledDisabledBanks($banks);
    }

    public function getBankNames($banks)
    {
        return \Models\Bank\Name::getNames($banks);
    }

    public function setPaymentBanksForMerchant($merchant, $input)
    {
        $banks = $this->repo->getMerchantMethods($merchant->getId());

        if ($banks === null)
        {
            $banks = new Methods\Entity;
            $banks->merchant()->associate($merchant);
        }

        return $this->setPaymentBanks($banks, $input);
    }

    public function setDefaultMethods($merchant)
    {
        $methods = (new Methods\Entity)->build();

        $methods->merchant()->associate($merchant);

        $methods->setCard(true);
        $methods->setAmex(true);
        $methods->setMobikwik(true);
        $methods->setPayzapp(true);

        $this->setAllPaymentBanks($methods);

        $this->repo->saveOrFail($methods);

        return $methods;
    }

    public function setAllPaymentBanks($methods)
    {
        $input = [
            'banks' => \Models\Payment\Processor\Netbanking::getAllBanks()
        ];

        $this->setPaymentBanks($methods, $input);
    }

    protected function setPaymentBanks($methods, $input)
    {
        (new Validator)->validateInput('addBanks', $input);

        $methods->setBanks($input['banks']);
        $this->repo->saveOrFail($methods);

        return $this->getEnabledDisabledBanks($methods);
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
