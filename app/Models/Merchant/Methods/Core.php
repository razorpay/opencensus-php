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
use RZP\Models\Emi;

use Config;

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

        // Setup workflow
        $workflow = $this->app['workflow']->setOriginal(clone $methods);

        $methods->setMethods($input);

        $this->checkPricing($merchant, $methods);

        // Trigger workflow
        $workflow->setDirty($methods)->handle();

        $this->saveAndNotifyOnSlack($merchant, $methods);

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

    public function getMethods(Merchant\Entity $merchant)
    {
        $methods = $this->getPaymentMethods($merchant);

        return $methods;
    }

    public function getFormattedMethods(Merchant\Entity $merchant)
    {
        $data = array(
            'entity'        => 'methods',
            'card'          => true,
            'amex'          => false,
            'netbanking'    => [],
            'wallet'        => [],
            'emi'           => false,
            'upi'           => false,
        );

        $methods = $this->getMethods($merchant);

        if ($methods !== null)
        {
            $data['card'] = $methods->isCardEnabled();
            $data['amex'] = $methods->isAmexEnabled();
            $netbankingEnabled = $methods->isNetbankingEnabled();
            if ($netbankingEnabled === true)
            {
                $banks = $methods->getSupportedBanks();

                $allSupportedBanks = Netbanking::removeDefaultDisableBanks($banks);

                $data['netbanking'] = $this->getBankNames($allSupportedBanks);
            }
            $data['wallet'] = $methods->getEnabledWallets();
            $data['upi'] = $methods->isUpiEnabled();
            $emi = $methods->isEmiEnabled();

            if ($emi === true)
            {
                $data['emi'] = $emi;

                $data['emi_subvention'] = $merchant->getEmiSubvention();

                $data['emi_plans'] = (new Emi\Service)->all();
            }
        }

        return $data;
    }

    public function getEnabledAndDisabledBanks($merchant)
    {
        $banks = $this->repo->methods->getMethodsForMerchant($merchant);

        return $this->getEnabledDisabledBanks($banks);
    }

    public function getEnabledBanks($methods)
    {
        $enabledDisabledBanks = $this->getEnabledDisabledBanks($methods);
        return $enabledDisabledBanks['enabled'];
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

    public function validatePricingForInternational($merchant, $plan)
    {
        if ($plan->hasInternationalPricing() === false)
        {
                throw new Exception\BadRequestValidationFailureException(
                    'Pricing not present for international.');
        }
    }

    public function setDefaultMethods($merchant)
    {
        $methods = (new Methods\Entity)->build();

        $methods->merchant()->associate($merchant);

        // No default methods are enabled for Marketplace accounts
        if ($merchant->isLinkedAccount() === false)
        {
            $methods->setCreditCard(true);
            $methods->setDebitCard(true);
            $methods->setMobikwik(true);
            $methods->setPayzapp(true);
            $methods->setPayumoney(true);
            $methods->setOlamoney(true);
            $methods->setFreecharge(true);
            $methods->setAirtelmoney(true);
            // Initializing Disabled bank with empty array
            $methods->setDisabledBanks([]);
        }

        $this->repo->saveOrFail($methods);

        return $methods;
    }

    public function setPaymentBanksForMerchant($merchant, $input)
    {
        if ((isset($input['banks']) === false) or
            (is_array($input['banks']) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Banks field is not an array');
        }
        /**
         *  Converting input new disabled banks based format
         */
        $input = [
            'disabled_banks' => Netbanking::getDisabledBanks($input['banks'])
        ];

        $method = $this->repo->methods->getMethodsForMerchant($merchant);

        return $this->disablePaymentBanks($method, $input);
    }

    protected function getPaymentMethods(Merchant\Entity $merchant)
    {
        $methods = $this->repo->methods->getMethodsForMerchant($merchant);

        if ($methods === null)
        {
            $methods = $this->setDefaultMethods($merchant);
        }

        return $methods;
    }

    protected function disablePaymentBanks($methods, $input)
    {
        (new Validator)->validateInput('addDisabledBanks', $input);

        $workflow = $this->app['workflow']
                         ->setEntity($methods->getEntity())
                         ->setOriginal(['disabled_banks' => $methods->getDisabledBanks()]);

        $methods->setDisabledBanks($input['disabled_banks']);

        $workflow->setDirty(['disabled_banks' => $methods->getDisabledBanks()])->handle();

        $this->repo->saveOrFail($methods);

        return $this->getEnabledDisabledBanks($methods);
    }

    /**
     * Method reads disabled_banks from database and
     * subtract them from all enabled banks
     * @param $methods
     * @return array
     */
    protected function getEnabledDisabledBanks(Entity $methods)
    {
        $disabled = $methods->getDisabledBanks();
        $enabled = $methods->getEnabledBanks();

        $data = array(
            'enabled' => $this->getBankNames($enabled),
            'disabled' => $this->getBankNames($disabled));

        return $data;
    }

    protected function saveAndNotifyOnSlack(Merchant\Entity $merchant, Entity $methods)
    {
        $data = $this->getEditedMethodsDifference($methods);

        $this->repo->saveOrFail($methods);

        if (empty($data) === false)
        {
            $label   = $merchant->getBillingLabel();
            $message = $merchant->getDashboardEntityLinkForSlack($label);
            $user    = $this->getInternalUsernameOrEmail();

            $message .= ' ' . $merchant->getEntity() . ' edited by ' . $user;

            $this->app['slack']->queue(
                $message,
                $data,
                [
                    'channel'  => Config::get('slack.channels.operations_log'),
                    'username' => 'Jordan Belfort',
                    'icon'     => ':boom:'
                ]
            );
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
        return Netbanking::getNames($banks);
    }
}
