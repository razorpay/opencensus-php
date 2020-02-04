<?php

namespace RZP\Models\Merchant\Methods;

use Config;

use RZP\Exception;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Pricing\Fee;
use RZP\Models\Card\Network;
use RZP\Models\Pricing\Plan;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Methods;
use RZP\Models\Feature\Constants;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Partner\Config as PartnerConfig;

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

    //
    // this always need to called from
    // proxy auth so merchant object is not
    // passed
    //
    public function editMethods(array $input)
    {
        $methods = $this->getPaymentMethods($this->merchant);

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $this->merchant->getId(),
                'input' => $input,
                'current_methods' => $methods->toArrayAdmin(),
            ]);

        $methods->setMethods($input);

        $this->repo->saveOrFail($methods);

        $methodsArray = $methods->toArrayPublic();

        return array_intersect_key($methodsArray, $input);
    }

    public function validatePricingPlanForMethods(Merchant\Entity $merchant, Plan $plan, Entity $methods)
    {
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

    public function checkCategorySubcategoryAndEnableEmi(Merchant\Entity $merchant, Entity $methods)
    {
        $merchantDetails = (new Merchant\Detail\Core())->getMerchantDetails($merchant);

        $category = $merchantDetails->getBusinessCategory();

        $subcategory = $merchantDetails->getBusinessSubCategory();

        if ((empty($category) === false) and
            (Emi\Constants::isCategoryOrSubcategoryBlacklisted($category, $subcategory) === false))
        {
            $methods->setMethods([Entity::EMI => true]);

            $this->repo->saveOrFail($methods);
        }
    }

    public function checkPricing(Merchant\Entity $merchant, Entity $methods, bool $defaultEmi = false)
    {
        $plan = $this->repo->pricing->getMerchantPricingPlan($merchant);

        if (($defaultEmi === true) and
            ($plan->hasMethod(Payment\Method::EMI) === false))
        {
            $emiPricing = $this->repo->pricing->getPricingPlanById(Fee::DEFAULT_EMI_PLAN_ID);

            $plan = $plan->merge($emiPricing);
        }

        $this->validatePricingPlanForMethods($merchant, $plan, $methods);
    }

    public function getMethods(Merchant\Entity $merchant)
    {
        $methods = $this->getPaymentMethods($merchant);

        return $methods;
    }

    public function getFormattedMethods(Merchant\Entity $merchant)
    {
        $data = [
            'entity'                     => E::METHODS,
            Payment\Method::CARD         => true,
            Entity::DEBIT_CARD           => true,
            Entity::CREDIT_CARD          => true,
            Entity::PREPAID_CARD         => true,
            Entity::CARD_NETWORKS        => [],
            Entity::CARD_SUBTYPE         => [],
            Payment\Gateway::AMEX        => false,
            Payment\Method::NETBANKING   => [],
            Payment\Method::WALLET       => [],
            Payment\Method::EMI          => false,
            Payment\Method::UPI          => false,
            Payment\Method::CARDLESS_EMI => [],
            Payment\Method::PAYLATER     => [],
            Entity::GOOGLE_PAY_CARDS     => false,
        ];

        $methods = $this->getMethods($merchant);

        $data[Payment\Method::CARD]  = $methods->isCardEnabled();
        $data[Entity::DEBIT_CARD]    = $methods->isDebitCardEnabled();
        $data[Entity::CREDIT_CARD]   = $methods->isCreditCardEnabled();
        $data[Entity::PREPAID_CARD]  = $methods->isPrepaidCardEnabled();
        $data[Entity::NACH]          = $methods->isNachEnabled();
        $data[Entity::CARD_NETWORKS] = $methods->getCardNetworks();
        $data[Entity::CARD_SUBTYPE]  = $methods->getCardSubtypes();
        $data[Payment\Gateway::AMEX] = $methods->isAmexEnabled();
        $netbankingEnabled           = $methods->isNetbankingEnabled();

        if ($netbankingEnabled === true)
        {
            $banks = $methods->getSupportedBanks();

            $allSupportedBanks = Netbanking::removeDefaultDisableBanks($banks);

            $data[Payment\Method::NETBANKING] = $this->getBankNames($allSupportedBanks);
        }

        $data[Payment\Method::WALLET]        = $methods->getEnabledWallets();
        $data[Payment\Method::UPI]           = $methods->isUpiEnabled();
        $data[Payment\Method::CARDLESS_EMI] =
                  $methods->isCardlessEmiEnabled() ? $this->getProviders($merchant, Payment\Method::CARDLESS_EMI) : [];

        $data[Payment\Method::PAYLATER] =
            $methods->isPayLaterEnabled() ? $this->getProviders($merchant, Payment\Method::PAYLATER) : [];

        if ($merchant->isFeatureEnabled(Constants::BANK_TRANSFER_ON_CHECKOUT) === true)
        {
            $data[Payment\Method::BANK_TRANSFER] = $methods->isBankTransferEnabled();
        }

        $emi = $methods->isEmiEnabled();

        if ($emi === true)
        {
            $data[Payment\Method::EMI] = $emi;

            $data['emi_subvention'] = $merchant->getEmiSubvention();

            $emiPlansAndOptions = (new Emi\Service)->getEmiPlansAndOptions();

            $data['emi_plans'] = $emiPlansAndOptions['plans'];

            $data['emi_options'] = $emiPlansAndOptions['options'];
        }

        if ($merchant->isRecurringEnabled() === true)
        {
            $data['recurring'] = [];

            $this->addRecurringCardsToMethods($merchant, $methods, $data['recurring']);

            $this->addRecurringEmandateToMethodsIfApplicable($merchant, $methods, $data['recurring']);

            $data['recurring'][Entity::NACH] = $methods->isNachEnabled();
        }

        if ($merchant->isFeatureEnabled(Constants::DISABLE_UPI_INTENT) === false)
        {
            $data['upi_intent'] = true;
        }

        if ($merchant->isFeatureEnabled(Constants::GOOGLE_PAY_CARDS) === true)
        {
            $data[Entity::GOOGLE_PAY_CARDS] = true;
        }

        return $data;
    }

    public function addRecurringCardsToMethods(
        Merchant\Entity $merchant,
        Methods\Entity $methods,
        array & $recurringData)
    {
        if ($methods->isCreditCardEnabled() === true)
        {
            $supportedNetworksForCreditCardRecurring = Payment\Gateway::getNetworksSupportedForCardRecurring();

            $recurringData['card']['credit'] = Network::getFullNames($supportedNetworksForCreditCardRecurring);
        }

        if ($merchant->isDebitRecurringEnabled() === true)
        {
            $supportedIssuersForDebitCardRecurring = Payment\Gateway::getIssuersSupportedForDebitCardRecurring();

            $recurringData['card']['debit'] = $this->getBankNames($supportedIssuersForDebitCardRecurring);
        }
    }

    public function addRecurringEmandateToMethodsIfApplicable(
        Merchant\Entity $merchant,
        Methods\Entity $methods,
        array & $recurringData)
    {
        //
        // We don't allow emandate for subscriptions currently.
        //
        if ($merchant->isFeatureEnabled(Constants::CHARGE_AT_WILL) === false)
        {
            return;
        }

        if ($methods->isEmandateEnabled() === false)
        {
            return;
        }

        $authTypes = Payment\AuthType::getAuthTypeForMethod(Payment\Method::EMANDATE);

        // this is temporary (read as hack), just to disable aadhaar auth type.
        if ((bool) Admin\ConfigKey::get(Admin\ConfigKey::BLOCK_AADHAAR_REG, true) === true)
        {
            $authTypes = [Payment\AuthType::NETBANKING, Payment\AuthType::DEBITCARD];
        }

        foreach ($authTypes as $authType)
        {
            if ($this->isTestMode() === true)
            {
                $banks = Payment\Gateway::getAvailableEmandateBanksForAuthType($authType);
            }
            else
            {
                $banks = $this->getEmandateBanksEnabled($merchant, $authType);
            }

            if (empty($banks) === false)
            {
                $banks = $this->getBankNames($banks);

                foreach ($banks as $ifsc => $name)
                {
                    $recurringData['emandate'][$ifsc]['auth_types'][] = $authType;
                    $recurringData['emandate'][$ifsc]['name'] = $name;
                }
            }
        }
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

    public function setDefaultMethods($merchant, Merchant\Entity $aggregatorMerchant = null)
    {
        $methods = (new Methods\Entity)->build();

        $methods->merchant()->associate($merchant);

        // No default methods are enabled for linked accounts
        if ($merchant->isLinkedAccount() === false)
        {
            $methodsEnabled = $this->setDefaultMethodsFromPartnerConfigsIfApplicable($methods, $aggregatorMerchant);

            if ($methodsEnabled === false)
            {
                $methods->setCreditCard(true);
                $methods->setDebitCard(true);
                $methods->setPrepaidCard(true);
                $methods->setMobikwik(true);
                $methods->setPayzapp(true);
                $methods->setPayumoney(true);
                // OlaMoney is facing fraud issues, not going to
                // enable by default for new merchants anymore.
                // Ref: https://razorpay.slack.com/archives/C0X84TUTH/p1568200366022300
                // $methods->setOlamoney(false);
                $methods->setFreecharge(true);
                $methods->setAirtelmoney(true);
                $methods->setAmazonpay(false);
                $methods->setBankTransfer(true);
                $methods->setAmex(true);
                $methods->setJiomoney(true);
                $methods->setPayLater(true);
                // Initializing Disabled bank with empty array
                $methods->setDisabledBanks([]);
            }
        }
        else
        {
            //
            // The following methods are enabled true by default
            // and we're disabling for linked accounts
            //
            $methods->setNetbanking(false);
            $methods->setCreditCard(false);
            $methods->setDebitCard(false);
            $methods->setPrepaidCard(false);
            $methods->setUpi(false);
        }

        $this->repo->saveOrFail($methods);

        return $methods;
    }

    /**
     * Check if partner have Default Payment methods set for submerchant.
     * If it exists then override payment methods over default.
     *
     * @param Entity               $methods
     * @param Merchant\Entity|null $aggregatorMerchant
     *
     * @return bool
     */
    private function setDefaultMethodsFromPartnerConfigsIfApplicable(Entity $methods, Merchant\Entity $aggregatorMerchant = null)
    {
        if ($aggregatorMerchant === null)
        {
            return false;
        }

        if (($aggregatorMerchant->isAggregatorPartner() === false) and ($aggregatorMerchant->isFullyManagedPartner() === false))
        {
            return false;
        }

        $defaultPartnerConfig = (new PartnerConfig\Core)->fetchAllDefaultConfigsByPartner($aggregatorMerchant);

        if (($defaultPartnerConfig === null) or (count($defaultPartnerConfig) === 0))
        {
            return false;
        }

        $defaultPaymentMethods = $defaultPartnerConfig->first()->getDefaultPaymentMethods();

        if (empty($defaultPaymentMethods) === true)
        {
            return false;
        }

        $methods->setMethods(Entity::$defaultPaymentMethodsForSubmerchantByPartner);

        foreach ($defaultPaymentMethods as $key => $value)
        {
            $methods->setAttribute($key, $value);
        }

        $this->trace->info(
            TraceCode::PARTNER_DEFAULT_PAYMENT_METHODS_TO_SUBMERCHANT,
            [
                'submerchant_id'          => $methods->getMerchantId(),
                'partner_id'              => $aggregatorMerchant->getId(),
                'default_payment_methods' => $defaultPaymentMethods,
            ]);

        return true;
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

    public function getPaymentMethods(Merchant\Entity $merchant): Entity
    {
        $methods = $this->repo->methods->getMethodsForMerchant($merchant);

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

    protected function getEmandateBanksEnabled(Merchant\Entity $merchant, $authType): array
    {
        $availableEmandateBanks = [];

        // @todo: Can be done by passing gateway
        // That way we can check if gateways are empty and return
        // empty array if it is
        $applicableEmandateTerminals = $this->repo
                                            ->terminal
                                            ->getEmandateTerminalsForMerchantAndSharedMerchant($merchant, $authType);

        $availableGatewaysForMerchant = $applicableEmandateTerminals->pluck(Terminal\Entity::GATEWAY);

        foreach ($availableGatewaysForMerchant as $availableGateway)
        {
            if (isset(Payment\Gateway::$gatewaysEmandateBanksMap[$availableGateway][$authType]) === true)
            {
                $availableEmandateBanks = array_merge(
                                                $availableEmandateBanks,
                    Payment\Gateway::$gatewaysEmandateBanksMap[$availableGateway][$authType]);
            }
        }

        return array_values(array_unique($availableEmandateBanks));
    }

    protected function getBankNames($banks)
    {
        return Netbanking::getNames($banks);
    }

    public function getProviders($merchant, $method)
    {
        $provider = [];

        $providers = $this->app['repo']->terminal->findByMerchantIdAndMethod($merchant['id'], $method);

        $providers = $providers->toArray();

        $providers = (array_unique(array_column($providers, 'gateway_acquirer')));

        foreach ($providers as $providerName)
        {
            $provider[$providerName] = true;
        }

        return $provider;
    }

    public function checkPaypalTerminalForCurrency($merchant, $currency)
    {
        $terminals = $this->repo
                          ->terminal
                          ->findByMerchantIdGatewayAndCurrency(
                              $merchant['id'],
                              Payment\Gateway::WALLET_PAYPAL,
                              $currency);

        $result = $terminals === null ? false : true;

        return $result;
    }
}
