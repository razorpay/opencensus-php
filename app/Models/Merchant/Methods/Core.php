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
use RZP\Trace\TraceCode;
use RZP\Models\Bank;

class Core extends Base\Core
{
    /**
     * @param Merchant\Entity $merchant
     * @param array $input
     * @return array
     */
    public function setPaymentMethods(Merchant\Entity $merchant, array $input)
    {
        $methods = $this->getPaymentMethods($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $merchant->getId(),
                'input' => $input,
                'current_methods' => $methods->toArrayAdmin(),
            ]);

        $methods->setMethods($input);

        $this->checkPricing($merchant, $methods);

        $this->repo->saveOrFail($methods);

        $this->notifyOnSlack($merchant, $methods);

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

        $this->validateInternationalPricingForMerchant($merchant, $plan);
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

    public function validateInternationalPricingForMerchant($merchant, $plan)
    {
        if (($merchant->isInternational()) and
            ($plan->hasInternationalPricing() === false))
        {
                throw new Exception\BadRequestValidationFailureException(
                    'International payment enabled, but pricing not present.');
        }
    }

    public function setDefaultMethods($merchant)
    {
        $methods = (new Methods\Entity)->build();

        $methods->merchant()->associate($merchant);

        $methods->setCard(true);
        $methods->setAmex(true);
        $methods->setMobikwik(true);
        $methods->setPayzapp(true);
        $methods->setPayumoney(true);

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

    protected function getPaymentMethods($merchant)
    {
        $methods = $this->repo->methods->getMerchantMethods($merchant->getId());

        if ($methods === null)
        {
            $methods = $this->setDefaultMethods($merchant);
        }

        return $methods;
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

    protected function notifyOnSlack(Merchant\Entity $merchant, Entity $methods)
    {
        $data = $this->getEditedMethodsDifference($methods);

        $this->repo->saveOrFail($merchant);

        if (empty($data) === false)
        {
            $label   = $merchant->getBillingLabel();
            $message = $merchant->getDashboardEntityLinkForSlack($label);

            $dashboardInfo = $this->app['basicauth']->getDashboardHeaders();

            $user = $dashboardInfo['admin_user'] ?: $dashboardInfo['merchant'];

            $message .= ' ' . $merchant->getEntity() . ' edited by ' . $user;

            $this->app['slack']->queue($message, $data, ['channel' => '#operations_log',
                                                         'username' => 'Jordan Belfort',
                                                         'icon' => ':boom:']);
        }
    }

    /**
     * Get difference between the original and updated attributes
     *
     * @param Entity $methods
     * @return array|null
     */
    protected function getEditedMethodsDifference(Entity $methods)
    {
        $original = $methods->getOriginalAttributesAgainstDirty();

        if ($original !== null)
        {
            $dirtyAttributes = $methods->getDirty();

            $data = array();

            foreach ($original as $key => $value)
            {
                $data[$key] = '*Old*: ' . $value . PHP_EOL . '*New*: ' . $dirtyAttributes[$key];
            }

            return $data;
        }
    }

    protected function getBankNames($banks)
    {
        return Bank\Name::getNames($banks);
    }
}
