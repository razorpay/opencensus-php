<?php

namespace Models\Merchant\Methods;

use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Models\Bank\IFSC;
use Models\Base;
use Models\Merchant;
use Models\Merchant\Methods;
use Models\Payment;
use Models\Payment\Processor\Netbanking;
use Models\Pricing;
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
        $methods = $this->getPaymentMethods($merchant);

        $methods->setMethods($input);

        $this->checkPricing($merchant, $methods);

        $this->repo->saveOrFail($methods);

        return $methods->toArray();
    }

    public function validatePricingPlanForMethods($merchant, $plan, $methods = null)
    {
        if ($methods === null)
        {
            $methods = $this->getPaymentMethods($merchant);
        }


        $methodsToCheck = Payment\Method::getAllPaymentMethods();

        foreach ($methodsToCheck as $method)
        {
            if (($methods->isMethodEnabled($method)) and
                ($plan->hasMethod($method) === false))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Pricing not present for method: ' . $method);
            }
        }

        if (($methods->isAmexEnabled()) and
            ($plan->hasNetworkAmex() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_RULE_FOR_AMEX_NOT_PRESENT);
        }

        if ($merchant->isInternational() and
            ($plan->hasInternationalPricing() === false))
        {
                throw new Exception\BadRequestValidationFailureException(
                    'International payment enabled, but pricing not present.');
        }
    }

    public function checkPricing($merchant, $methods = null)
    {
        if ($methods === null)
        {
            $methods = $this->getPaymentMethods($merchant);
        }

        $plan = (new Pricing\Repository)->getMerchantPricingPlan($merchant);

        $this->validatePricingPlanForMethods($merchant, $plan, $methods);
    }

    public function getMethods($merchant)
    {
        $methods = $this->getPaymentMethods($merchant);

        $supportedBanks = Netbanking::getSupportedBanks($this->mode, $merchant->isTPVRequired());

        $methods->setBanks($supportedBanks);

        return $methods;
    }

    public function getEnabledAndDisabledBanks($merchant)
    {
        $banks = $this->repo->getMerchantMethods($merchant->getId());

        return $this->getEnabledDisabledBanks($banks);
    }

    protected function getPaymentMethods($merchant)
    {
        $methods = $this->repo->getMerchantMethods($merchant->getId());

        if ($methods === null)
        {
            $methods = $this->setDefaultMethods($merchant);
        }

        return $methods;
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

    public function getBankNames($banks)
    {
        return \Models\Bank\Name::getNames($banks);
    }
}
