<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Pricing;
use RZP\Models\Terminal;

class Core extends Base\Core
{
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

        $this->valdiateInternationalPricingForMerchant($merchant, $plan);
    }

    public function checkPricing($merchant, $methods = null)
    {
        if ($methods === null)
        {
            $methods = $this->getPaymentMethods($merchant);
        }

        $plan = $this->repo->pricing->getMerchantPricingPlan($merchant);

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
        $banks = $this->repo->methods->getMerchantMethods($merchant->getId());

        return $this->getEnabledDisabledBanks($banks);
    }

    public function valdiateInternationalPricingForMerchant($merchant, $plan)
    {
        if (($merchant->isInternational()) and
            ($plan->hasInternationalPricing() === false))
        {
                throw new Exception\BadRequestValidationFailureException(
                    'International payment enabled, but pricing not present.');
        }
    }

    protected function getPaymentMethods($merchant)
    {
        $methods = $this->repo->methods->getMerchantMethods($merchant->getId());

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
        $methods->setPayumoney($true);

        $this->setAllPaymentBanks($methods);

        $this->repo->saveOrFail($methods);

        return $methods;
    }

    public function setAllPaymentBanks($methods)
    {
        $input = [
            'banks' => Netbanking::getAllBanks()
        ];

        $this->setPaymentBanks($methods, $input);
    }

    public function setPaymentBanksForMerchant($merchant, $input)
    {
        $banks = $this->repo->methods->getMerchantMethods($merchant->getId());

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

        $disabled = Netbanking::getDisabledBanks($enabled);

        $data = array(
            'enabled' => $this->getBankNames($enabled),
            'disabled' => $this->getBankNames($disabled));

        return $data;
    }

    public function getBankNames($banks)
    {
        return \RZP\Models\Bank\Name::getNames($banks);
    }
}
