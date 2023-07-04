<?php

namespace RZP\Models\Merchant\Methods;

use App;
use Carbon\Carbon;
use Config;

use RZP\Exception;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Pricing\Fee;
use RZP\Models\Card\Network;
use RZP\Models\Pricing\Plan;
use RZP\Constants\Entity as E;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Methods;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Fpx;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Models\Pricing\Feature as Feature;
use RZP\Models\Feature\Constants as Features;
use RZP\Services\KafkaProducer;
use RZP\Models\Emi\CreditEmiProvider;
use RZP\Models\Emi\PaylaterProvider;
use RZP\Models\Emi\CardlessEmiProvider;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Base\UniqueIdEntity;

class Core extends Base\Core
{
    /**
     * @param Merchant\Entity $merchant
     * @param array $input
     * @return array
     */
    public $gatewayTerminalValidation = array(
        Methods\Entity::PAYTM,
    );

    const GATEWAY = 'gateway';
    const STATUS  = 'status';
    const ENABLED = 'enabled';

    const SET_DEFAULT_METHODS_LOCK_TIMEOUT        = 0.07;  //seconds

    const defaultCreditEmiProvidersWhitelisted = [

        Entity::CREDIT_EMI_PROVIDERS  => [

            CreditEmiProvider::HDFC => '1',
            CreditEmiProvider::SBIN => '0',
            CreditEmiProvider::UTIB => '1',
            CreditEmiProvider::ICIC => '1',
            CreditEmiProvider::AMEX => '1',
            CreditEmiProvider::BARB => '1',
            CreditEmiProvider::CITI => '1',
            CreditEmiProvider::HSBC => '1',
            CreditEmiProvider::INDB => '1',
            CreditEmiProvider::KKBK => '1',
            CreditEmiProvider::RATN => '1',
            CreditEmiProvider::SCBL => '1',
            CreditEmiProvider::YESB => '1',
            CreditEmiProvider::ONECARD => '1',
            CreditEmiProvider::BAJAJ => '0',
            CreditEmiProvider::FDRL => '1'

        ]
    ];
    const defaultCardlessEmiProvidersWhitelisted = [

        Entity::CARDLESS_EMI_PROVIDERS  => [

            CardlessEmiProvider::ZESTMONEY  => '0',
            CardlessEmiProvider::EARLYSALARY  => '1',
            CardlessEmiProvider::WALNUT369 => '0',
            CardlessEmiProvider::HDFC => '0',
            CardlessEmiProvider::ICIC => '0',
            CardlessEmiProvider::BARB => '0',
            CardlessEmiProvider::KKBK => '0',
            CardlessEmiProvider::FDRL => '0',
            CardlessEmiProvider::IDFB => '0',
            CardlessEmiProvider::HCIN => '0',
            CardlessEmiProvider::KRBE => '0',
            CardlessEmiProvider::CSHE => '0',
            CardlessEmiProvider::TVSC => '0',
        ]
    ];
    const defaultPaylaterProvidersWhitelisted =[

        Entity::PAYLATER_PROVIDERS  => [

            PaylaterProvider::GETSIMPL => '0',
            PaylaterProvider::LAZYPAY => '0',
            PaylaterProvider::HDFC => '0',
            PaylaterProvider::ICIC => '1',

        ]

    ];


    public function setPaymentMethods(Merchant\Entity $merchant, array $input)
    {
        $methods = $this->getPaymentMethods($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $merchant->getId(),
                'input' => $input,
                'current_methods' => $methods->toArrayAdmin(),
                'category' => $merchant->getCategory(),
            ]);

        if(isset($input[Entity::OFFLINE]) === true and $input[Entity::OFFLINE] == true and
        $merchant->isFeatureEnabled(Constants::OFFLINE_PAYMENT_ON_CHECKOUT) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD,null,[
                "internal_error_code" =>ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD,
            ]);
        }

        if (isset($input['custom_text']) === true)
        {
            $this->setMerchantCustomTextForMethods($merchant->getMethods(), $input);

            unset($input['custom_text']);
        }

        // To avoid workflow creation.If the input is null, it means we are just updating the custom_text.
        // So no update in methods entity.
        if (empty($input) === true)
        {
            return $methods->toArray();
        }

        // Setup workflow
        $workflow = $this->app['workflow']->setOriginal(clone $methods);

        $mcc = $merchant->getCategory();
        if((isset($input['emi']['credit']) ===  true && $input['emi']['credit'] === '1') || (isset($input['emi']['debit']) === true && $input['emi']['debit'] === '1'))
        {
            (new Validator)->validateCategoryForEmi($mcc);
        }

        if(isset($input['card_networks']['AMEX']) === true && $input['card_networks']['AMEX'] === '1')
        {
            (new Validator)->validateCategoryForAmexCardNetwork($mcc);
        }

        if (isset($input['paylater']) === true && $input['paylater'] === '1') {
            (new Validator)->validateCategoryForPaylater($mcc);
        }

        if (isset($input['amazonpay']) === true && $input['amazonpay'] === '1') {
            (new Validator)->validateCategoryForAmazonPay($mcc);
        }

        if (isset($input[Methods\Entity::CARD_NETWORKS]) === true)
        {
            $inputCardNetworks = $input[Methods\Entity::CARD_NETWORKS];

            foreach ($inputCardNetworks as $cardNetwork => $value)
            {
                switch ($cardNetwork)
                {
                    case Network::AMEX:
                        $methods->setAmex($value);
                        break;

                    case Network::VISA:
                        $methods->setVisaCard($value);
                        break;

                    case Network::MC:
                        $methods->setMasterCard($value);
                        break;

                    case Network::MAES:
                        $methods->setMaestroCard($value);
                        break;

                    case Network::RUPAY:
                        $methods->setRupayCard($value);
                        break;

                    case Network::BAJAJ:
                        $methods->setBajajCard($value);
                        break;

                    case Network::JCB:
                        $methods->setJcbCard($value);
                        break;

                    case Network::DICL:
                        $methods->setDinersCard($value);
                        break;

                    case Network::DISC:
                        $methods->setDiscCard($value);
                        break;

                    case Network::UNP:
                        $methods->setUnpCard($value);
                        break;
                }
            }

            unset($input[Methods\Entity::CARD_NETWORKS]);
        }


        $methods->setMethods($input);

        $this->checkPricing($merchant, $methods, false, false);

        // Trigger workflow
        $workflow->setDirty($methods)->handle();

        $this->saveAndNotifyOnSlack($merchant, $methods);

        return $methods->toArray();
    }

    public function editMethods(array $input, Merchant\Entity $merchant = null)
    {
        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        $methods = $this->getPaymentMethods($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $merchant->getId(),
                'input' => $input,
                'current_methods' => $methods->toArrayAdmin(),
            ]);

        $methods->setMethods($input);

        $this->repo->saveOrFail($methods);

        $this->pushMethodUpdateEventToKafka($merchant,$methods);

        $methodsArray = $methods->toArrayPublic();

        return array_intersect_key($methodsArray, $input);
    }

    public function validatePricingPlanForMethods(Merchant\Entity $merchant, Plan $plan, Entity $methods, bool $skipFeatureCheck = true)
    {
        $methodsToCheck = Payment\Method::getAllPaymentMethods();

        foreach ($methodsToCheck as $method)
        {
            if ($method === Payment\Method::COD)
            {
                // for Cod we are adding default pricing incase explicit pricing is not present
                // hence the check can be ignored here
                continue;
            }

            if (($methods->isMethodEnabled($method)) and
                ($plan->hasMethodForFeature($method,Feature::PAYMENT, $skipFeatureCheck) === false))
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
            $methods->setMethods([Entity::EMI => [EmiType::CREDIT => '1', EmiType::DEBIT => '1']]);

            $this->repo->saveOrFail($methods);

            $this->pushMethodUpdateEventToKafka($merchant,$methods);
        }
    }

    public function checkPricing(Merchant\Entity $merchant, Entity $methods, bool $defaultEmi = false, bool $skipfeatureCheck = true)
    {
        $plan = $this->repo->pricing->getMerchantPricingPlan($merchant);

        if (($defaultEmi === true) and
            ($plan->hasMethodForFeature(Payment\Method::EMI,Feature::PAYMENT, $skipfeatureCheck) === false))
        {
            $emiPricing = $this->repo->pricing->getPricingPlanById(Fee::DEFAULT_EMI_PLAN_ID);

            $plan = $plan->merge($emiPricing);
        }

        $this->validatePricingPlanForMethods($merchant, $plan, $methods, $skipfeatureCheck);
    }

    public function getMethods(Merchant\Entity $merchant)
    {
        $methods = $this->getPaymentMethods($merchant);

        return $methods;
    }

    private function getUpiPaymentMethod(Merchant\Entity $merchant)
    {
        return $this->repo->methods->isUpiEnabledForMerchant($merchant->getId());
    }

    public function getUpiMethodForMerchant(Merchant\Entity $merchant)
    {
        $data = [
            'entity'                    => E::METHODS,
            Entity::UPI                 => false,
        ];

        $data[Payment\Method::UPI] = $this->getUpiPaymentMethod($merchant);
        return $data;
    }

    public function getFormattedMethods(Merchant\Entity $merchant)
    {
        $data = [
            'entity'                            => E::METHODS,
            Payment\Method::CARD                => true,
            Entity::DEBIT_CARD                  => true,
            Entity::CREDIT_CARD                 => true,
            Entity::PREPAID_CARD                => true,
            Entity::CARD_NETWORKS               => [],
            Entity::CARD_SUBTYPE                => [],
            Payment\Gateway::AMEX               => false,
            Payment\Method::NETBANKING          => [],
            Payment\Method::WALLET              => [],
            Payment\Method::EMI                 => false,
            Payment\Method::UPI                 => false,
            Payment\Method::CARDLESS_EMI        => [],
            Payment\Method::PAYLATER            => [],
            Entity::GOOGLE_PAY_CARDS            => false,
            Payment\Method::APP                 => [],
            Entity::GPAY                        => false,
            Entity::EMI_TYPES                   => [],
            Entity::DEBIT_EMI_PROVIDERS         => [],
//            Entity::CREDIT_EMI_PROVIDERS         => [],
            Payment\Method::INTL_BANK_TRANSFER  => [],
            Payment\Method::FPX                 => [],
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
        $data[Payment\Method::APP]   = $methods->getApps();
        $data[Entity::DEBIT_EMI_PROVIDERS] = $methods->getConsolidatedEnabledDebitEmiProviders();
//        $data[Entity::CREDIT_EMI_PROVIDERS] = $methods->getConsolidatedEnabledCreditEmiProviders();
        $data[Entity::EMI_TYPES] = $methods->getEmiTypes();
        $data[Entity::COD] = $methods->isCodEnabled();
        $data[Entity::OFFLINE] = $methods->isOfflineEnabled();
        $fpxEnabled = $methods->isFpxEnabled();
        $data[Entity::INTL_BANK_TRANSFER] = $methods->getIntlBankTransferEnabledForMerchant();

        if ($netbankingEnabled === true)
        {
            $banks = $methods->getSupportedBanks();

            $allSupportedBanks = Netbanking::removeDefaultDisableBanks($banks);

            $data[Payment\Method::NETBANKING] = $this->getBankNames($allSupportedBanks);
        }

        if($fpxEnabled === true)
        {
            $banks = $methods->getFPXSupportedBanks();

            $data[Payment\Method::FPX] = Fpx::getDisplayNames($banks);
        }

        $data[Payment\Method::WALLET]        = $methods->getEnabledWallets();
        $data[Payment\Method::UPI]           = $methods->isUpiEnabled();
        $data[Payment\Method::CARDLESS_EMI] =
                  $methods->isCardlessEmiEnabled() ? $this->getProviders($merchant, Payment\Method::CARDLESS_EMI,$methods) : [];

        $data[Payment\Method::PAYLATER] =
            $methods->isPayLaterEnabled() ? $this->getProviders($merchant, Payment\Method::PAYLATER, $methods) : [];

        $data[Entity::SODEXO] = $methods->isSodexoEnabled();

        if ($merchant->isFeatureEnabled(Constants::BANK_TRANSFER_ON_CHECKOUT) === true)
        {
            $data[Payment\Method::BANK_TRANSFER] = $methods->isBankTransferEnabled();
        }

        if ($methods->isEmiEnabled() === true)
        {
            $data[Payment\Method::EMI] = true;

            $data['emi_subvention'] = $merchant->getEmiSubvention();

            $emiPlansAndOptions = (new Emi\Service)->getEmiPlansAndOptions();

            $data['emi_plans'] = $emiPlansAndOptions['plans'];

            $data['emi_options'] = $emiPlansAndOptions['options'];
        }

        if ($methods->isCredEnabled() === true)
        {
            $this->addCustomTextForCredIfApplicable($merchant, $methods, $data);
        }

        if ($methods->isInAppEnabled() !== null) {
            $data[Entity::IN_APP] = $methods->isInAppEnabled();
        }

        if ($merchant->isRecurringEnabled() === true)
        {
            $data['recurring'] = [];

            $this->addRecurringCardsToMethods($merchant, $methods, $data['recurring']);

            $this->addRecurringEmandateToMethodsIfApplicable($merchant, $methods, $data['recurring']);

            $this->addRecurringUpiToMethodsIfApplicable($merchant, $methods, $data['recurring']);

            $data['recurring'][Entity::NACH] = $methods->isNachEnabled();
        }

        if ($merchant->isFeatureEnabled(Constants::DISABLE_UPI_INTENT) === false)
        {
            $data['upi_intent'] = true;
        }

        if ($merchant->isFeatureEnabled(Constants::UPI_OTM) === true)
        {
            $data['upi_otm'] = true;
        }

        if ($merchant->isFeatureEnabled(Constants::GOOGLE_PAY_CARDS) === true)
        {
            $data[Entity::GOOGLE_PAY_CARDS] = true;
        }

        if ($merchant->isGooglePayEnabled())
        {
            $data[Entity::GPAY] = true;
        }

        return $data;
    }

    public function addUpiType(Merchant\Entity $merchant, array $data):array
    {
        $methods = $this->getMethods($merchant);

        $data[Entity::UPI_TYPE] = $methods->getUpiTypes();

        if (isset($data['intent']) && $data['upi_intent'])
        {
            $data['upi_intent'] = $data[Entity::UPI_TYPE][UpiType::INTENT];
        }

        return $data;

    }

    public function addRecurringCardsToMethods(
        Merchant\Entity $merchant,
        Methods\Entity $methods,
        array & $recurringData)
    {
        if (($methods->isCreditCardEnabled() === true) or ($methods->isPrepaidCardEnabled() === true))
        {

            // If atleast 1 3ds Amex recurring type terminal is present, it will send Amex Recurring in Preferences
            $recurringAmexTerminals = $this->repo->terminal->getRecurringTerminalsByMidAndGateway($merchant->getId(), Gateway::AMEX);
            $supportedNetworksForCreditCardRecurring = Payment\Gateway::getNetworksSupportedForCardRecurring();

            if (empty($recurringAmexTerminals) === true)
            {
                unset($supportedNetworksForCreditCardRecurring[array_search(Network::AMEX, $supportedNetworksForCreditCardRecurring)]);
            }

            if ($methods->isCreditCardEnabled() === true)
            {
                $recurringData['card']['credit'] = Network::getFullNames($supportedNetworksForCreditCardRecurring);
            }

            if ($methods->isPrepaidCardEnabled() === true)
            {
                $recurringData['card']['prepaid'] = Network::getFullNames($supportedNetworksForCreditCardRecurring);
            }
        }

        if ($methods->isDebitCardEnabled() === true)
        {
            $supportedIssuersForDebitCardRecurring = Payment\Gateway::getIssuersSupportedForDebitCardRecurring();

            $recurringData['card']['debit'] = $this->getBankNames($supportedIssuersForDebitCardRecurring);
        }
    }

    public function addRecurringUpiToMethodsIfApplicable(
        Merchant\Entity $merchant,
        Methods\Entity $methods,
        array & $recurringData)
    {
        if ($methods->isUpiEnabled() === false)
        {
            return;
        }

        // This is to enable merchants to test upi recurring on test mode with sharp terminal.
        if (($this->mode === Mode::TEST) and ($this->app->runningUnitTests() === false))
        {
            $recurringData['upi'] = true;
        }

        $recurringUpiTerminals = $this->repo->terminal->getUpiRecurringTerminalsByMid($merchant->getId());

        if (empty($recurringUpiTerminals) === false)
        {
            $recurringData['upi'] = true;
        }
    }

    public function addRecurringEmandateToMethodsIfApplicable(
        Merchant\Entity $merchant,
        Methods\Entity $methods,
        array & $recurringData)
    {
        if ($merchant->isFeatureEnabled(Constants::CHARGE_AT_WILL) === false and
            $merchant->isFeatureEnabled(Constants::SUBSCRIPTIONS) === false)
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
        else
        {
            if ($merchant->isFeatureEnabled(Constants::ESIGN) === false)
            {
                $authTypes = array_diff($authTypes, [Payment\AuthType::AADHAAR]);
            }
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

            $banks = Payment\Gateway::removeEmandateRegistrationDisabledBanks($banks);

            if($authType === "netbanking")
            {
                $banks = Payment\Gateway::removeNetbankingEmandateRegistrationDisabledBanks($banks);
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

    public function addCustomTextForCredIfApplicable(
        Merchant\Entity $merchant,
        Methods\Entity $methods,
        array & $data)
    {
        $key = $methods->getCustomTextCacheKey();

        $text = $this->app['cache']->get($key);

        if (isset($text['cred']) === true)
        {
            $data['custom_text']['cred'] = $text['cred'];
        }

        return;
    }

    //method to add ACH and swift payment modes for intl_bank_transfer method
    public function addIntlBankTransferMethodsIfApplicable(Methods\Entity $methods, array & $data)
    {
        $intl_bank_transfer_modes = $methods->getIntlBankTransferEnabledModes();

        foreach (Methods\Entity::getAddonMethodsList(Methods\Entity::INTL_BANK_TRANSFER) as $mode)
        {
            $data[Methods\Entity::INTL_BANK_TRANSFER][$mode] = isset($intl_bank_transfer_modes[$mode]) ? (int)$intl_bank_transfer_modes[$mode] : 0 ;
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

    public function validateRuleBasedFeatureFlagForMerchant(string $merchantId) :bool
    {
        $featureResult = $this->repo->feature->findMerchantWithFeatures($merchantId, [Features::RULE_BASED_ENABLEMENT, Features::SPR_DISABLE_METHOD_RESET])->toArray();

        $featureNames = [];
        foreach ($featureResult as $feature)
        {
            array_push($featureNames, $feature["name"]);
        }

        if (in_array(Features::RULE_BASED_ENABLEMENT, $featureNames)
            || in_array(Features::SPR_DISABLE_METHOD_RESET, $featureNames))
        {
            return true;
        }

        return false;
    }

    public function validateCategoryUpdateForMerchant(string $categoryToBeUpdated, \RZP\Models\Merchant\Entity $merchant, bool $forceIgnoreValidation)
    {
        switch ($categoryToBeUpdated)
        {
            case '5094':
            case '5944':
            case '7631':
                //$forceIgnoreValidation is the input of the reset_methods
                if (!$forceIgnoreValidation) //reset_methods = false
                {
                    (new Validator)->validateEmiOptionsForJewelleryMerchants($categoryToBeUpdated, $merchant);
                }
                break;
        }

        if ($forceIgnoreValidation) //reset_methods = true
        {
            (new Validator)->validateAndAllowResetMerchantMethods($merchant->getId());
        }
    }

    public function setMethods($merchant, Merchant\Entity $aggregatorMerchant = null)
    {

        $this->trace->info(TraceCode::SET_PAYMENT_METHODS_UNDER_MUTEX_LOCK,
            [
                'merchant_id' => $merchant->getId(),
            ]
        );

        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = $merchant->getId();

        $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($merchant, $aggregatorMerchant)
            {
                (new Methods\Core)->setDefaultMethods($merchant, $aggregatorMerchant);
            },
            self::SET_DEFAULT_METHODS_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SET_DEFAULT_METHODS_ALREADY_IN_PROGRESS);
    }

    public function setDefaultMethods($merchant, Merchant\Entity $aggregatorMerchant = null)
    {
        $methods = $this->repo->methods->getMethodsForMerchant($merchant);

        if($methods !== null)
        {
            $methods->merchant()->associate($merchant);

            return $methods;
        }

        $methods = (new Methods\Entity)->build();

        $methods->merchant()->associate($merchant);

        // No default methods are enabled for linked accounts
        if ($merchant->isLinkedAccount() === false)
        {
            $methodsEnabled = $this->setDefaultMethodsFromPartnerConfigsIfApplicable($methods, $aggregatorMerchant);

            if ($methodsEnabled === false)
            {
                if($merchant->getCountry() === 'MY'){
                    $methods->setCreditCard(true);
                    $methods->setDebitCard(true);
                    $methods->setUpi(false);
                    $methods->setNetbanking(false);
                    $methods->setMobikwik(false);
                    $methods->setPrepaidCard(false);
                    $methods->setBankTransfer(false);
                }else{
                    $methods->setCreditCard(true);
                    $methods->setDebitCard(true);
                    $methods->setPrepaidCard(true);
                    $methods->setMobikwik(true);
                    $methods->setPayzapp(true);
                    $methods->setPayumoney(true);
                    $methods->setOlamoney(true);
                    $methods->setFreecharge(true);
                    $methods->setAirtelmoney(true);
                    $methods->setAmazonpay(false);
                    $methods->setBankTransfer(true);
                    $methods->setJiomoney(true);
                    $methods->setPayLater(true);
                    $methods->setPhonepeSwitch(true);

                    //disable UPI for certain merchants
                    if($this->isUPIPaymentMethodAllowed($merchant) === true)
                    {
                        $methods->setUpi(false);
                    }
                }
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

        $this->trace->info(TraceCode::SAVE_MERCHANT_METHODS,
            [
                'merchant_id'  => $merchant->getId(),
                'methods'      => $methods
            ]
        );

        $this->repo->saveOrFail($methods);

        $this->pushMethodUpdateEventToKafka($merchant,$methods);

        return $methods;
    }

    public function resetDefaultMethodsBasedOnMerchantCategories(Merchant\Entity $merchant)
    {
        $category  = $merchant->getCategory();
        $category2 = $merchant->getCategory2();
        $orgId     = $merchant->getOrgId();

        if($merchant->isFeatureEnabled(Features::SPR_DISABLE_METHOD_RESET)) {
            $this->trace->info(
                TraceCode::MERCHANT_METHODS_RESET_BASED_ON_CATEGORY_REQUEST,
                [
                    'merchant_id' => $merchant->getId(),
                    'METHOD_RESET_SKIPPED_FOR_SPR'    => true
                ]
            );

            return;
        }

        $this->trace->info(
            TraceCode::MERCHANT_METHODS_RESET_BASED_ON_CATEGORY_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'category'    => $category,
                'category2'   => $category2
            ]
        );

        $methods = $merchant->methods;

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        //Org Id(s) to be added as blacklisted RazorX experiment till when it will use default methods, if removed uses org specific methods
        $variantFlag = $this->app->razorx->getTreatment($orgId, 'ORG_DEFAULT_PAYMENT_METHODS', $mode);

        $defaultMethods = DefaultMethodsForCategory::getDefaultMethodsFromMerchantCategories($category, $category2, $orgId, $variantFlag);

        $merchantDetails = (new Merchant\Detail\Core())->getMerchantDetails($merchant);
        // disable phone for business type unregistered and others.
        if ($merchantDetails->isUnregisteredBusiness() === true || $merchantDetails->getBusinessType() === Merchant\Detail\BusinessType::OTHER)
        {
            $defaultMethods[Entity::PHONEPE] = false;
        }

        //disable UPI for certain merchants
        if($this->isUPIPaymentMethodAllowed($merchant) === true)
        {
            $defaultMethods[Entity::UPI] = false;
        }

        $this->resetDefaultMethodsBasedOnMerchantPricingPlan($merchant, $defaultMethods);

        if ((is_null($methods) === true) or (is_null($defaultMethods) === true))
        {
            $this->trace->info(
                TraceCode::MERCHANT_METHODS_RESET_BASED_ON_CATEGORY_NOT_APPLICABLE,
                [
                    'merchant_id' => $merchant->getId(),
                    'category'    => $category,
                    'category2'   => $category2
                ]
            );

            return;
        }

        foreach ($defaultMethods as $key => $value)
        {
            if ($key === Entity::EMI)
            {
                if ($value === false)
                {
                    $value = [
                        EmiType::CREDIT => '0',
                        EmiType::DEBIT  => '0',
                    ];
                }
                else
                {
                    $value = [
                        EmiType::CREDIT => '1',
                        EmiType::DEBIT  => '1',
                    ];
                    $methods->setMethods(self::defaultCreditEmiProvidersWhitelisted);
                }
            }
            if ($key === Entity::PAYLATER and $value === true)
            {
                $methods->setMethods(self::defaultPaylaterProvidersWhitelisted);
            }
            if ($key === Entity::CARDLESS_EMI and $value === true)
            {
                $methods->setMethods(self::defaultCardlessEmiProvidersWhitelisted);
            }

            $methods->setAttribute($key, $value);
        }

        $this->repo->saveOrFail($methods);

        $this->pushMethodUpdateEventToKafka($merchant,$methods);
    }

    private function resetDefaultMethodsBasedOnMerchantPricingPlan(Merchant\Entity $merchant, $defaultMethods)
    {
        $pricingPlanId = $merchant->getPricingPlanId();

        $plan = $this->repo->pricing->getPricingPlanByIdWithoutOrgId($pricingPlanId);

        $methodsToCheck = Payment\Method::getAllPaymentMethods();

        foreach ($methodsToCheck as $method)
        {
            if (isset($defaultMethods[$method]) and $defaultMethods[$method] === true)
            {
                $defaultMethods[$method] = $plan->hasMethod($method);
            }
        }

        if ($plan->hasNetworkAmex() === false)
        {
            $defaultMethods[Entity::AMEX] = false;
        }

        if ($plan->hasMethod(Entity::CARD) === false)
        {
            $defaultMethods[Entity::CREDIT_CARD] = false;

            $defaultMethods[Entity::DEBIT_CARD] = false;

            $defaultMethods[Entity::PREPAID_CARD] = false;
        }

        $this->trace->info(
            TraceCode::PRICING_PLAN_DEFAULT_METHODS,
            [
                'merchant_id' => $merchant->getId(),
                'plan_id' => $merchant->getPricingPlanId(),
                'default_methods' => $defaultMethods,
            ]
        );
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

        return $this->disablePaymentBanks($method, $input,$merchant);
    }

    public function getPaymentMethods(Merchant\Entity $merchant): Entity
    {
        $methods = $this->repo->methods->getMethodsForMerchant($merchant);

        return $methods;
    }

    protected function disablePaymentBanks($methods, $input,$merchant)
    {
        (new Validator)->validateInput('addDisabledBanks', $input);

        $workflow = $this->app['workflow']
                         ->setEntity($methods->getEntity())
                         ->setOriginal(['disabled_banks' => $methods->getDisabledBanks()]);

        $methods->setDisabledBanks($input['disabled_banks']);

        $workflow->setDirty(['disabled_banks' => $methods->getDisabledBanks()])->handle();

        $this->repo->saveOrFail($methods);

        $this->pushMethodUpdateEventToKafka($merchant,$methods);

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

            $this->pushMethodUpdateEventToKafkaWrapper($merchant,$data);
        }
    }

    /**
     * Method pushes Kafka events for Payments Methods enabled/disabled
     * @param Merchant\Entity $merchant
     * @param Entity $methods
     */
    protected function pushMethodUpdateEventToKafka(Merchant\Entity $merchant, Entity $methods) {
        $data = $this->getEditedMethodsDifference($methods);

        if (empty($data) === false) {
            $this->pushMethodUpdateEventToKafkaWrapper($merchant, $methods);
        }
    }

    /**
     * Method pushes Kafka events for Payments Methods enabled/disabled
     * @param Merchant\Entity $merchant
     * @param array $methods Methods changed are alone sent
     */
    protected function pushMethodUpdateEventToKafkaWrapper(Merchant\Entity $merchant, array $methods)
    {
        try {
            $topic = env('TERMINALS_MERCHANT_METHODS_UPDATE_TOPIC', 'events.merchant_methods_update.v2.live');

            $dashboardInfo = $this->app['basicauth']->getDashboardHeaders();

            $properties = [
                "admin_user_email" => $dashboardInfo['admin_email'] ?? $dashboardInfo['admin_username'] ?? Merchant\Constants::DASHBOARD_INTERNAL,
                "methods" => $methods,
                "url" => $merchant->getDashboardEntityLink(),
                "merchant" => [
                    'id' => $merchant->getId(),
                    'name' => $merchant->getBillingLabel(),
                    'mcc' => $merchant->getCategory(),
                    'category' => $merchant->getCategory2(),
                ],
            ];

            $metaDetails = [
                'trackId' => $this->app['req.context']->getTrackId(),
            ];

            $event = [
                "event_name" => "payment.methods.enablement",
                "event_type" => "merchant_methods_update",
                "event_group" => "terminals",
                "version" => "v2",
                "event_timestamp" => Carbon::now()->getTimestamp(),
                "producer_timestamp" => Carbon::now()->getTimestamp(),
                "source" => "api",
                "mode" => $this->app['env'],
                "properties" => $properties,
                "metadata" => $metaDetails,
                "read_key" => array("merchant.id"),
                "write_key" => "merchant.id"
            ];

            (new KafkaProducer($topic, stringify($event)))->Produce();

            $this->trace->info(TraceCode::MERCHANT_METHODS_UPDATE_KAFKA_PUSH_SUCCESS,
                [
                    "Topic" => $topic,
                    "Merchant.ID" => $merchant->getId(),
                ]
            );
        }
        catch(\Throwable $ex) {
            $this->trace->traceException($ex);
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

        if ($authType === Payment\AuthType::AADHAAR)
        {
            $availableEmandateBanks = Payment\Gateway::removeAadhaarEmandateRegistrationDisabledBanks($availableEmandateBanks);
        }

        return array_values(array_unique($availableEmandateBanks));
    }

    protected function getBankNames($banks)
    {
        return Netbanking::getNames($banks);
    }

    public function getProviders($merchant, $method, Methods\Entity $methods = null)
    {
        $provider = [];

        $terminals = $this->app['repo']->terminal->findByMerchantIdAndMethod($merchant['id'], $method);

        $terminals = $terminals->toArray();

        if($this->mode === Mode::TEST && (empty($terminals)))
        {
            return $this->getProvidersforTestMode($method);
        }

        if($methods == null)
        {
            $methods = $this->getMethods($merchant);
        }

        if ($method === Payment\Method::PAYLATER)
        {
            $providers = (array_unique(array_column($terminals, 'gateway_acquirer')));

            $enabledBanks = (array_column($terminals, 'enabled_banks'));
            $enabledBanks = array_unique(array_flatten($enabledBanks));
            $enabledBanks = array_filter($enabledBanks);

            $enabledProviders = array_map('strtolower', $enabledBanks);

            $providers = array_merge($providers, $enabledProviders);

            $paylaterProviders = $methods->getEnabledPaylaterProviders();

            foreach ($providers as $index => $instrument) {

                if (isset($paylaterProviders[$instrument]) == false or  $paylaterProviders[$instrument] == 0) {

                    unset($providers[$index]);

                }

                $merchantWhitelistedForLazypay = (new MerchantCore())->isMerchantWhitelistedForLazypay($merchant);

                if($instrument === Payment\Gateway::LAZYPAY and !$merchantWhitelistedForLazypay)
                {
                    unset($providers[$index]);
                }

            }

            $this->sortPaylaterProviders($providers);
        }

        if ($method === Payment\Method::CARDLESS_EMI)
        {
            $providers = [];

            foreach ($terminals as $terminal)
            {
                $terminalProviders = null;

                if ((Payment\Processor\CardlessEmi::isMultilenderProvider($terminal[Terminal\Entity::GATEWAY_ACQUIRER])) and
                    (empty($terminal[Terminal\Entity::ENABLED_BANKS]) === false))
                {
                    $terminalProviders = array_map('strtolower', $terminal[Terminal\Entity::ENABLED_BANKS]);
                }
                else
                {
                    $terminalProviders[] = $terminal[Terminal\Entity::GATEWAY_ACQUIRER];
                }

                $providers = array_unique(array_merge($providers,$terminalProviders));
            }

            $cardlessEmiProviders = $methods->getEnabledCardlessEmiProviders();

            foreach ($providers as $index => $instrument) {

                $isDisabledInstrument = in_array($instrument, CardlessEmiProvider::$disabledInstruments, true);

                if ($isDisabledInstrument === true or isset($cardlessEmiProviders[$instrument]) == false or
                    $cardlessEmiProviders[$instrument] == 0)
                {

                    unset($providers[$index]);

                }

            }

        }

        foreach ($providers as $providerName)
        {
            //for skipping providers that support other banks for paylater
            if (($method === Payment\Method::PAYLATER) and (Payment\Processor\PayLater::isMultilenderProvider($providerName)))
            {
                continue;
            }

            $provider[$providerName] = true;
        }

        return $provider;
    }

    /**
     * Sorts the providers in the given order. If a provider is not present in
     * the order array then it gets pushed to the end of the provider array.
     *
     * @param array $provider
     * @param array $order
     * @return void
     */
    protected function sortPaylaterProviders(array &$provider, array $order = PayLater::CHECKOUT_DISPLAY_ORDER): void
    {
        $order = array_flip($order);
        $lastPosition = count($order);
        usort($provider, static function ($provider1, $provider2) use ($order, $lastPosition) {
            return ($order[$provider1] ?? $lastPosition) <=> ($order[$provider2] ?? $lastPosition);
        });
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

    public function enableOrDisableMethodsBasedOnTerminals($merchant, array & $data, $mode)
    {
        if($mode == Mode::TEST)
        {
            foreach($this->gatewayTerminalValidation as $gateway)
            {
                $params = [
                    Merchant\Entity::MERCHANT_ID => $merchant->getId(),
                    self::GATEWAY => $gateway,
                    self::STATUS  => 'activated',
                    self::ENABLED => 1,
                ];

                $terminals = $this->repo->terminal->getByParams($params);

                if($terminals->count() !== 0)
                {
                    $data['wallet'][$gateway] = true;
                }
            }
        }

        return $data;
    }

    /**
     * Store merchant specific text to be shown on checkout in cache.Doing it only for cred for now
     *
     * @param Entity    $methods
     * @param array     $customText
     *
     */
    protected function setMerchantCustomTextForMethods(Methods\Entity $methods, $input)
    {
        if((isset($input['custom_text']['cred']) === true) &&
           (($methods->isCredEnabled() === true) ||
            (isset($input['apps']['cred']) === true)))
        {
            $key = $methods->getCustomTextCacheKey();

            $data = [
                'cred' => $input['custom_text']['cred']
            ];

            $this->app['cache']->forever($key, $data);
        }

        return;
    }

    public function getProvidersforTestMode($method)
    {
        $provider = [];
        $enabledBanks = [];

        if ($method === Payment\Method::CARDLESS_EMI)
        {
            $providers =  CardlessEmi::getCardlessEmiDirectAquirers();

            foreach ($providers as $providerName)
            {
                if (CardlessEmi::isMultilenderProvider($providerName))
                {
                    $enabledBanks = array_unique(array_merge($enabledBanks, CardlessEmi::getSupportedBanksForMultilenderProvider($providerName)));
                }
                else
                {
                    array_push($enabledBanks, $providerName);
                }
            }
        }

        if ($method === Payment\Method::PAYLATER)
        {
            $providers =  PayLater::getPaylaterDirectAquirers();
            foreach ($providers as $providerName)
            {
                if (PayLater::isMultilenderProvider($providerName))
                {
                    $enabledBanks = array_unique(array_merge($enabledBanks, PayLater::getSupportedBanksForMultilenderProvider($providerName)));
                }
                else
                {
                    array_push($enabledBanks, $providerName);
                }
            }
        }

        $enabledBanks = array_map('strtolower', $enabledBanks);

        foreach ($enabledBanks as $providerName)
        {
            $provider[$providerName] = true;
        }

        return $provider;
    }

    public function isUPIPaymentMethodAllowed($merchant)
    {
        if($merchant->getOrgId() !== OrgEntity::RAZORPAY_ORG_ID)
        {
            return false;
        }

        if ($merchant->isBusinessBankingEnabled() === true or $merchant->isLinkedAccount() === true)
        {
            return false;
        }

        if($merchant->isFeatureEnabled(FeatureConstants::OPTIMIZER_ONLY_MERCHANT) === true)
        {
            return false;
        }

        $upiDedicatedTerminalExpt = (new MerchantCore())->isRazorxExperimentEnable($merchant->getId(), RazorxTreatment::UPI_DEDICATED_TERMINAL);

        if($upiDedicatedTerminalExpt === false)
        {
            return false;
        }

        return true;
    }
}
