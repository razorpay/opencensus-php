<?php

namespace RZP\Models\Merchant\Methods;

use App;
use Carbon\Carbon;
use Config;

use RZP\Constants\Environment;
use RZP\Exception;
use RZP\Jobs\CrossBorder\CrossBorderCommonUseCases;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Payment\Processor\App as AppMethod;
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
use RZP\Models\PaymentsUpi\PayerAccountType;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Models\Pricing\Feature as Feature;
use RZP\Models\Feature\Constants as Features;
use RZP\Services\KafkaProducer;
use RZP\Models\Emi\CreditEmiProvider;
use RZP\Models\Emi\DebitProvider;
use RZP\Models\Emi\PaylaterProvider;
use RZP\Models\Emi\CardlessEmiProvider;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\Processor\Netbanking as NetbankingProcessor;

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
    const EMANDATE = 'emandate';
    const NAME = 'name';
    const AUTH_TYPES = 'auth_types';
    const BANK_CODE = 'bank_code';
    const IS_MERGED_BANK = 'is_merged_bank';
    const METHOD_ENABLED = 'method_enabled';
    const TERMINAL_AVAILABLE = 'terminal_available';

    const SET_DEFAULT_METHODS_LOCK_TIMEOUT        = 1;  //seconds

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
            CreditEmiProvider::FDRL => '1',
            CreditEmiProvider::IDFB => '1',
            CreditEmiProvider::AUBL => '1'


        ]
    ];

    const defaultDebitEmiProvidersWhitelisted = [

        Entity::DEBIT_EMI_PROVIDERS  => [

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
            CardlessEmiProvider::LIQUILOANS=>'0',
            CardlessEmiProvider::INSTANT_EMI => '0',
            CardlessEmiProvider::SHOPSE => '0',
        ]
    ];
    const defaultPaylaterProvidersWhitelisted =[

        Entity::PAYLATER_PROVIDERS  => [

            PaylaterProvider::GETSIMPL => '0',
            PaylaterProvider::LAZYPAY => '0',
            PaylaterProvider::HDFC => '0',
            PaylaterProvider::ICIC => '1',
            PaylaterProvider::AMAZONPAY=>'0',
            PaylaterProvider::RZPXPOSTPAID => '0',
            PaylaterProvider::ZIP => '0',
            PaylaterProvider::KLARNA => '0',
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

        if (isset($input[Methods\Entity::APPS]))
        {
            $emerchantPayApps = AppMethod::$supportedApps[AppMethod::EMERCHANTPAY];
            foreach ($emerchantPayApps as $app)
            {
                if($input[Methods\Entity::APPS][$app] === '1')
                {
                    $payload = [
                        'mode' => $this->mode,
                        'action' => CrossBorderCommonUseCases::DISABLE_ON_DEMAND_SETTLEMENT,
                        'merchant_id' => $merchant->getId()
                    ];
                    CrossBorderCommonUseCases::dispatch($payload)->delay(rand(60,1000) % 601);
                    break;
                }
            }
        }

        if (isset($input[Methods\Entity::PAYLATER_PROVIDERS]))
        {
            $pproPaylaters = [PaylaterProvider::ZIP, PaylaterProvider::KLARNA] ;
            foreach ($pproPaylaters as $paylater)
            {
                if($input[Methods\Entity::PAYLATER_PROVIDERS][$paylater] === '1')
                {
                    $payload = [
                        'mode' => $this->mode,
                        'action' => CrossBorderCommonUseCases::DISABLE_ON_DEMAND_SETTLEMENT,
                        'merchant_id' => $merchant->getId()
                    ];
                    CrossBorderCommonUseCases::dispatch($payload)->delay(rand(60,1000) % 601);
                    break;
                }
            }
        }

        if ((isset($input[Methods\Entity::ALIPAY]) && $input[Methods\Entity::ALIPAY] === 1) || (isset($input[Methods\Entity::GOPAY]) && $input[Methods\Entity::GOPAY] === 1) ||
            (isset($input[Methods\Entity::DOKU]) && $input[Methods\Entity::DOKU] === 1) || (isset($input[Methods\Entity::OVO]) && $input[Methods\Entity::OVO] === 1) ||
            (isset($input[Methods\Entity::LINKAJA]) && $input[Methods\Entity::LINKAJA] === 1) || (isset($input[Methods\Entity::KLARNA]) && $input[Methods\Entity::KLARNA] === 1) ||
            (isset($input[Methods\Entity::ZIP]) && $input[Methods\Entity::ZIP] === 1))
        {
            $payload = [
                'mode' => $this->mode,
                'action' => CrossBorderCommonUseCases::DISABLE_ON_DEMAND_SETTLEMENT,
                'merchant_id' => $merchant->getId()
            ];
            CrossBorderCommonUseCases::dispatch($payload)->delay(rand(60,1000) % 601);
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

        if (
            isset($input[Entity::IN_APP]) or
            isset($input[Entity::IN_APP_CREDIT_CARD]) or
            isset($input[Entity::IN_APP_AUTOPAY]) or
            isset($input[Entity::IN_APP_CREDIT_LINE]))
        {
            $this->handleEnableDisableForInAppPaymentMethods($methods, $input, $mcc);
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

        $methods->exists = true;

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

    public function editAllMethods(array $input, Merchant\Entity $merchant = null)
    {
        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        $methods = $this->getPaymentMethods($merchant);

        $methods->exists = true;

        $this->trace->info(
            TraceCode::MERCHANT_METHODS_EDIT,
            [
                'merchant_id' => $merchant->getId(),
                'input' => $input,
                'current_methods' => $methods->toArrayAdmin(),
            ]);

        if (isset($input['card'])){
            $methods->setAttribute('card', $input['card']);
            unset($input["card"]);
        }

        $methods->setMethods($input);

        (new Methods\Repository())->methodsDualWrite($methods);

        $methodsArray = $methods->toArrayPublic();

        return array_intersect_key($methodsArray, $input);
    }

    public function validatePricingPlanForMethods(Merchant\Entity $merchant, Plan $plan, Entity $methods, bool $skipFeatureCheck = true)
    {
        $methodsToCheck = Payment\Method::getAllPaymentMethods();

        foreach ($methodsToCheck as $method)
        {
            if (($method === Payment\Method::COD) or ($method === Payment\Method::RAZORPAY_ACCOUNT))
            {
                // for Cod we are adding default pricing incase explicit pricing is not present
                // for razorpay_account method, pricing is always zero which is set in pricing calculator
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

        $skipMethodResetForSubmerchant = $this->isSkipMethodResetForSubmerchant($merchant->getId());

        if($skipMethodResetForSubmerchant === true)
        {
            return;
        }

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
            Payment\Method::DUITNOW_PAY         => false,
            Payment\Method::GIFT_CARDS          => [],
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
        $data[Entity::OFFLINE_DEBIT_EMI_PROVIDERS] = $methods->getConsolidatedOfflineDebitEmiProviders();
        $data[Entity::OFFLINE_CREDIT_EMI_PROVIDERS] = $methods->getConsolidatedOfflineCreditEmiProviders();
        //$data[Entity::CREDIT_EMI_PROVIDERS] = $methods->getConsolidatedEnabledCreditEmiProviders();
        $data[Entity::EMI_TYPES] = $methods->getEmiTypes();
        $data[Entity::COD] = $methods->isCodEnabled();
        $data[Entity::OFFLINE] = $methods->isOfflineEnabled();
        $fpxEnabled = $methods->isFpxEnabled();
        $data[Payment\Method::GIFT_CARDS] = $methods->isGiftCardsEnabled();
        $data[Entity::INTL_BANK_TRANSFER] = $this->getInternationalBankTransferMethods($methods);
        $data[Payment\Method::DUITNOW_PAY] = $methods->isDuitNowPayEnabled();

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

        if($methods->isPayLaterEnabled() === true && $methods->isPaypalEnabled() === true)
        {
            $data[Payment\Method::PAYLATER][Gateway::PAYPAL] = true;
        }

        if($methods->isPayLaterEnabled() === true && $methods->isAtomeEnabled() === true)
        {
            $data[Payment\Method::PAYLATER][Gateway::ATOME] = true;
        }

        $data[Entity::SODEXO] = $methods->isSodexoEnabled();

        if ($merchant->isFeatureEnabled(Constants::BANK_TRANSFER_ON_CHECKOUT) === true)
        {
            $data[Payment\Method::BANK_TRANSFER] = $methods->isBankTransferEnabled();
        }

        // These variables are required for keeping the value returned from emi service
        $debitEmiProviders = [];

        $emiTypes = [];

        if ($methods->isEmiEnabled() === true)
        {
            $data[Payment\Method::EMI] = true;

            $data['emi_subvention'] = $merchant->getEmiSubvention();

            $emiPlansAndOptions = (new Emi\Service)->getEmiPlansAndOptions();

            $data['emi_plans'] = $emiPlansAndOptions['plans'];

            $data['emi_options'] = $emiPlansAndOptions['options'];

            $debitEmiProviders = $emiPlansAndOptions['debit_emi_providers'];

            $emiTypes = $emiPlansAndOptions['emi_types'];
        }
        else if($merchant->isFeatureEnabled(FeatureConstants::RAAS))
        {
            $emiService = (new Emi\Service);

            if($emiService->shouldFetchEmiPlansFromProviders())
            {
                $data[Payment\Method::EMI] = true;

                $data['emi_subvention'] = $merchant->getEmiSubvention();

                $emiPlansAndOptions = $emiService->getEmiPlansFromProviders();

                $data['emi_plans'] = $emiPlansAndOptions['plans'];

                $data['emi_options'] = $emiPlansAndOptions['options'];

                $debitEmiProviders = $emiPlansAndOptions['debit_emi_providers'];

                $emiTypes = $emiPlansAndOptions['emi_types'];
            }
        }

        // setting the value as 1 for all the providers which are returned from emi service
        foreach ($debitEmiProviders as $provider)
        {
            $data[Entity::DEBIT_EMI_PROVIDERS][$provider] = 1;
        }

        // setting the value as true in case razorpay debit/credit is disabled but on provider it's enabled
        foreach ($emiTypes as $type => $flag)
        {
            if(isset($data[Entity::EMI_TYPES])
                && isset($data[Entity::EMI_TYPES][$type])
                && $data[Entity::EMI_TYPES][$type] === false
                && $flag === true)
            {
                $data[Entity::EMI_TYPES][$type] = true;
            }
        }

        if ($methods->isCredEnabled() === true)
        {
            $this->addCustomTextForCredIfApplicable($merchant, $methods, $data);
        }

        $data[Entity::UPI_CONFIG] = [];

        if ($methods->isInAppEnabled() !== null)
        {
            $data[Entity::IN_APP] = $methods->isInAppEnabled();

            $data[Entity::UPI_CONFIG][Entity::IN_APP][\RZP\Models\Upi\Turbo\Constants::PAYER_ACCOUNT_TYPE] = [
                PayerAccountType::PAYER_ACCOUNT_TYPE_CREDIT       => $methods->isInAppCreditCardEnabled() === true,
                PayerAccountType::PAYER_ACCOUNT_TYPE_BANK_ACCOUNT => $methods->isInAppEnabled() === true,
            ];
        }

        if ($merchant->isRecurringEnabled() === true)
        {
            $data['recurring'] = [];

            $this->addRecurringCardsToMethods($merchant, $methods, $data['recurring']);

            $this->addRecurringEmandateToMethodsIfApplicable($merchant, $methods, $data['recurring']);

            $this->addRecurringUpiToMethodsIfApplicable($merchant, $methods, $data['recurring']);

            $data['recurring'][Entity::NACH] = $methods->isNachEnabled();

            if ($merchant->getCountry() === 'MY')
            {
                $data['recurring'][Payment\Method::WALLET][Entity::TOUCHNGO] = $methods->isTouchngoEnabled();
            }

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

    public function getInternationalBankTransferMethods(Methods\Entity $methods):array
    {
        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity($methods->getMerchantId(),Gateway::CURRENCY_CLOUD);
        if (isset($mii) === false || $mii->isInternationalVirtualAccountDisabled() === true) {
            return [];
        }
        return $methods->getIntlBankTransferEnabledForMerchant();
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
            $recurringData['upi_autopay'] = [
                UpiType::COLLECT => true,
                UpiType::INTENT => true,
            ];
        }

        $merchantIds = [$merchant->getId()];

        $upiMethodExperiment = $this->evaluateSplitzExperimentforUpiAutopayMethod();

        // If experiment is not enable then it will find shared terminals using shared merchant id
        if($upiMethodExperiment === false) {
            array_push($merchantIds, Merchant\Account::SHARED_ACCOUNT);
        }
        $recurringUpiTerminals = $this->repo->terminal->getUpiRecurringTerminalsByMid($merchantIds);

        if (empty($recurringUpiTerminals) === false)
        {
            $recurringData['upi'] = true;   //this is deprecated and on frontend we'll not use the upi field
            $recurringData['upi_autopay'] = [
                UpiType::COLLECT => $recurringUpiTerminals->isCollectTerminal(),
                UpiType::INTENT => $recurringUpiTerminals->isPay(),
            ];
        }
    }

    private function evaluateSplitzExperimentforUpiAutopayMethod()
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.show_upi_autopay_method_on_dashboard')
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->info(TraceCode::UPI_AUTOPAY_SPLITZ_METHOD_FAILURE,
                [
                    'error' => $ex->getMessage(),
                ]
            );
        }

        return false;
    }

    public function getMethodsForSubscriptionSettings(Merchant\Entity $merchant, $method): array
    {
        $methodsEnabled = $this->getMethods($merchant);

        $data = [];
        $data[$method][self::METHOD_ENABLED] = 0;
        $data[$method][self::TERMINAL_AVAILABLE] = 0;

        if($method === 'card')
        {
            $data[$method][self::TERMINAL_AVAILABLE] = 1; // for card always keep terminal available as true by default
            if (($methodsEnabled->isCreditCardEnabled() === true) or ($methodsEnabled->isPrepaidCardEnabled() === true)
                    or ($methodsEnabled->isDebitCardEnabled() === true))
            {
                $data[$method][self::METHOD_ENABLED] = 1;
            }
        }
        else if ($method === 'emandate')
        {
            if ($methodsEnabled->isEmandateEnabled() === true)
            {
                $data[$method][self::METHOD_ENABLED] = 1;
            }

            if($this->IsEmandateTerminalAvailable($merchant) === true)
            {
                $data[$method][self::TERMINAL_AVAILABLE] = 1;
            }
        }
        else if ($method === 'upi')
        {
            if ($methodsEnabled->isUpiEnabled() === true)
            {
                $data[$method][self::METHOD_ENABLED] = 1;
            }


            $upiMethodExperiment = $this->evaluateSplitzExperimentforUpiAutopayMethod();

            $merchantIds = [$merchant->getId()];

            if($upiMethodExperiment === false) {
                array_push($merchantIds, Merchant\Account::SHARED_ACCOUNT);
            }

            $recurringUpiTerminals = $this->repo->terminal->getUpiRecurringTerminalsByMid($merchantIds);

            if (empty($recurringUpiTerminals) === false)
            {
                $data[$method][self::TERMINAL_AVAILABLE] = 1;
            }
        }

        return $data;
    }

    protected function IsEmandateTerminalAvailable(Merchant\Entity $merchant): bool
    {
        $authTypes = Payment\AuthType::getAuthTypeForMethod(Payment\Method::EMANDATE);

        foreach ($authTypes as $authType)
        {
            $applicableEmandateTerminals = $this->repo
                ->terminal
                ->getEmandateTerminalsForMerchantAndSharedMerchant($merchant, $authType);

            if(empty($applicableEmandateTerminals) === false)
            {
                return true;
            }
        }
        return false;
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
        $experimentName = $this->app['config']->get('app.bank_data_via_npci_api_experiment');
        if ($this->getSplitzResponse($merchant->getMerchantId(), $experimentName) === 'enable') {

            $this->trace->info(TraceCode::BANK_DATA_VIA_NPCI_API,[
                'merchant_id' => $merchant->getMerchantId(),
                'experiment_name' => $experimentName,
            ]);

            if ($this->isTestMode() === true) {
                $recurringData[self::EMANDATE] = Payment\Gateway::getFilteredEmandateBanks($authTypes);
            } else {
                if (!isset($recurringData[self::EMANDATE]) || !is_array($recurringData[self::EMANDATE])) {
                    $recurringData[self::EMANDATE] = [];
                }
                $banksForAuthType = $this->getEmandateBanksEnabledNew($authTypes);

                $recurringData[self::EMANDATE] = $banksForAuthType[self::EMANDATE];
            }
        } else {
            foreach ($authTypes as $authType) {
                if ($this->isTestMode() === true) {
                    $banks = Payment\Gateway::getAvailableEmandateBanksForAuthType($authType);
                } else {
                    $banks = $this->getEmandateBanksEnabled($merchant, $authType);
                }

                $banks = Payment\Gateway::removeEmandateRegistrationDisabledBanks($banks);

                if ($authType === "netbanking") {
                    $banks = Payment\Gateway::removeNetbankingEmandateRegistrationDisabledBanks($banks);
                }

                if (empty($banks) === false) {
                    $banks = $this->getBankNames($banks);

                    foreach ($banks as $ifsc => $name) {
                        $recurringData[self::EMANDATE][$ifsc][self::AUTH_TYPES][] = $authType;
                        $recurringData[self::EMANDATE][$ifsc][self::NAME] = $name;

                        $mergedBankIfsc = Payment\Gateway::ENACH_NPCI_NB_MERGED_BANK_CODE_MAPPING[$ifsc] ?? null;

                        if ($mergedBankIfsc !== null) {
                            $recurringData[self::EMANDATE][$ifsc][self::IS_MERGED_BANK] = true;

                            $recurringData[self::EMANDATE][$ifsc][self::BANK_CODE] = $mergedBankIfsc;
                        } else {
                            $recurringData[self::EMANDATE][$ifsc][self::IS_MERGED_BANK] = false;
                        }
                    }
                }
            }
        }
    }

    public function getSplitzResponse(string $id, string $experimentId)
    {
        $properties = [
            'id'            => $id,
            'experiment_id' => $experimentId,
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
            'properties' => $properties,
            'response' => $response,
        ]);

        return $response['response']['variant']['name'] ?? '';
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
        $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity($methods->getMerchantId(),Gateway::CURRENCY_CLOUD);
        if (isset($mii) === false || $mii->isInternationalVirtualAccountDisabled() === true) {
            return [];
        }
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

    public function setMethods($merchant, Merchant\Entity $aggregatorMerchant = null, string $source=null )
    {

        $this->trace->info(TraceCode::SET_PAYMENT_METHODS_UNDER_MUTEX_LOCK,
            [
                'merchant_id' => $merchant->getId(),
                'source' => $source
            ]
        );
        $mutex = App::getFacadeRoot()['api.mutex'];
        $mutexKey= $merchant->getId()."_". Merchant\Constants::SET_METHOD_MUTEX_SUFFIX;;

        $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($merchant, $aggregatorMerchant)
            {
                $startTime = millitime();
                (new Methods\Core)->setDefaultMethods($merchant, $aggregatorMerchant);
                $this->trace->histogram(Metric::SET_DEFAULT_METHODS_LATERNCY_METRIC, millitime()-$startTime);
            },
            self::SET_DEFAULT_METHODS_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SET_DEFAULT_METHODS_ALREADY_IN_PROGRESS,
            2, 300,600);
    }

    public function setDefaultMethods($merchant, Merchant\Entity $aggregatorMerchant = null)
    {
        $methods=$this->repo->methods->getMethodsForMerchantV2($merchant);
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
            if(OrgEntity::isOrgCurlec($merchant->getOrgId())){
                $methods->setMobikwik(false);
                $methods->setBankTransfer(false);
            }
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

        $skipMethodResetForSubmerchant = $this->isSkipMethodResetForSubmerchant($merchant->getId());

        if($skipMethodResetForSubmerchant === true)
        {
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

        /*Disable UPI method by default for regular PG merchants*/

        if($this->shouldDisableUpiByDefault($merchant) === true)
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
                    $methods->setMethods(self::defaultDebitEmiProvidersWhitelisted);
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

            switch (true)
            {
                //for additional wallets like payzapp getters check in attribute array Entity::ADDITIONAL_WALLETS.
                //Their setters either don't exist or would not return correct value if set using $methods->setAttribute($key, $value)
                case in_array($key, Entity::getAllAdditionalWalletNames()):
                    $additionalWallets = $methods->getAttribute(Entity::ADDITIONAL_WALLETS);

                    if(($value === true) && (in_array($key, $additionalWallets) === false)) {
                        array_push($additionalWallets, $key);
                    }

                    if(($value === false) && (in_array($key, $additionalWallets) === true)) {
                        unset($additionalWallets[$key]);
                    }

                    $methods->setAttribute(Entity::ADDITIONAL_WALLETS, $additionalWallets);
                    break;

                case $key === Entity::SODEXO:
                    $addonMethods = $methods->getAttribute(Entity::ADDON_METHODS);
                    $addonMethods[Entity::CARD][Entity::SODEXO] = $value;
                    $methods->setAttribute(Entity::ADDON_METHODS, $addonMethods);
                    break;

                case $key === Entity::DUITNOW_PAY:
                    $addonMethods = $methods->getAttribute(Entity::ADDON_METHODS);
                    $addonMethods[Entity::DUITNOW_PAY][Entity::DUITNOW_PAY] = $value;
                    $methods->setAttribute(Entity::ADDON_METHODS, $addonMethods);

                 case $key === Entity::GIFT_CARDS:
                     $addonMethods = $methods->getAttribute(Entity::ADDON_METHODS);
                     $addonMethods[Entity::GIFT_CARDS][Entity::RAZORPAY_GIFTCARD] = $value;
                     $methods->setAttribute(Entity::ADDON_METHODS, $addonMethods);

                default:
                    $methods->setAttribute($key, $value);
            }
        }

        //Setting default disabled banks upon activation and mcc update flows.
        $methods->setDisabledBanks(NetbankingProcessor::DEFAULT_DISABLED_BANKS);

        $this->repo->saveOrFail($methods);

        $this->trace->info(
            TraceCode::MERCHANT_METHODS_RESET_UPON_ACTIVATION,
            [
                'merchant_id' => $merchant->getId(),
                'methods' => $merchant->methods->toArray(),
            ]
        );

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

        // allowing only for platform partner types(aggregator, pure platform, fully_managed)
        if (!$aggregatorMerchant->isPartner() || $aggregatorMerchant->isResellerPartner())
        {
            return false;
        }

        if ($aggregatorMerchant->isPurePlatformPartner())
        {
            $defaultPaymentMethods = $this->getDefaultPaymentMethodsForPurePlatformPartner($aggregatorMerchant);
        }
        else
        {
            $defaultPaymentMethods = $this->getDefaultPaymentMethodsForNonPurePlatformPartner($aggregatorMerchant);
        }

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

    protected function getDefaultPaymentMethodsForNonPurePlatformPartner(Merchant\Entity $aggregatorMerchant)
    {
        $defaultPartnerConfig = (new PartnerConfig\Core)->fetchAllDefaultConfigsByPartner($aggregatorMerchant);

        if ($defaultPartnerConfig->isEmpty())
        {
            return false;
        }

        return $defaultPartnerConfig->first()->getDefaultPaymentMethods();
    }

    protected function getDefaultPaymentMethodsForPurePlatformPartner(Merchant\Entity $aggregatorMerchant)
    {
        $oauthAppId = $this->app['basicauth']->getOAuthApplicationId() ?? null;

        if($oauthAppId == null)
            return null;

        $partnerConfig = $this->repo->partner_config->getApplicationConfig($oauthAppId);

        return optional($partnerConfig)->getDefaultPaymentMethods();
    }

    protected function getDefaultPaymentMethodsForPartnerApplication(?string $applicationId)
    {
        if($applicationId == null)
            return null;

        $partnerConfig = $this->repo->partner_config->getApplicationConfig($applicationId);

        return optional($partnerConfig)->getDefaultPaymentMethods();
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

    protected function getEmandateBanksEnabledNew($authTypes): array
    {
        $recurringData[self::EMANDATE] = [];
        // Fetch all bank data from API
        $data = Payment\Gateway::fetchBankDataFromApi();

        foreach ($data as $bankCode => $bank) {
            // Check if bank supports at least one of the given auth types
            $matchingAuthTypes = array_intersect($authTypes, $bank['auth_types'] ?? []);

            if (!empty($matchingAuthTypes)) {
                // Add bank to the response but only with the matching auth types
                $recurringData[self::EMANDATE][$bankCode] = $bank;
                $recurringData[self::EMANDATE][$bankCode]['auth_types'] = array_values($matchingAuthTypes); // Only include matching auth types
            }
        }

        return $recurringData;
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

            $enabledPaylater = (array_column($terminals, 'enabled_paylaters'));
            $enabledPaylater = array_unique(array_flatten($enabledPaylater));
            $enabledPaylater = array_filter($enabledPaylater);

            $enabledProviders = array_map('strtolower', $enabledPaylater);

            $providers = array_merge($providers, $enabledProviders);

            $paylaterProviders = $methods->getEnabledPaylaterProviders();

            if (in_array(PaylaterProvider::GETSIMPLOPTIMIZER, $providers) and isset($paylaterProviders[PaylaterProvider::GETSIMPL]) === true and
             $paylaterProviders[PaylaterProvider::GETSIMPL] === 1) {
                $paylaterProviders[PaylaterProvider::GETSIMPLOPTIMIZER] =1;
            }

            $whitelistedInstruments = (new MerchantCore())->getWhitelistedPaylaterInstruments($merchant);

            foreach ($providers as $index => $instrument) {

                $isDisabledInstrument = in_array($instrument, PaylaterProvider::$disabledInstruments, true);

                if ($isDisabledInstrument === true or isset($paylaterProviders[$instrument]) == false or $paylaterProviders[$instrument] == 0)
                {
                    unset($providers[$index]);
                }

                $isExperimentCheckRequired = array_key_exists($instrument, PaylaterProvider::$experimentCheckRequiredPaylaterProviders);

                if($isExperimentCheckRequired === true and !in_array($instrument,$whitelistedInstruments)){
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

            $whitelistedInstruments = (new MerchantCore())->getWhitelistedCardlessEMIInstruments($merchant);

            foreach ($providers as $index => $instrument) {

                $isDisabledInstrument = in_array($instrument, CardlessEmiProvider::$disabledInstruments, true);

                if ($isDisabledInstrument === true or isset($cardlessEmiProviders[$instrument]) == false or
                    $cardlessEmiProviders[$instrument] == 0)
                {
                    unset($providers[$index]);
                }

                $isExperimentCheckRequired = array_key_exists($instrument, CardlessEmiProvider::$experimentCheckRequiredCardlessEmiProviders);

                if($isExperimentCheckRequired === true and !in_array($instrument,$whitelistedInstruments)){
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

        if (isset($provider[PaylaterProvider::GETSIMPLOPTIMIZER]) === true and
        $provider[PaylaterProvider::GETSIMPLOPTIMIZER] === true) {
            unset($provider[PaylaterProvider::GETSIMPLOPTIMIZER]);
            $terminals = array_filter($terminals, function($terminal) {
                return $terminal['gateway_acquirer'] === PaylaterProvider::GETSIMPLOPTIMIZER;
            });

            $enabledPaylater = (array_column($terminals, 'enabled_paylaters'));
            $enabledPaylater = array_unique(array_flatten($enabledPaylater));
            $enabledPaylater = array_filter($enabledPaylater);

            foreach ($enabledPaylater as $providerName)
            {
                $provider[$providerName] = true;
            }
            // If no paylater found then default  simpl and simpl pay in 3 enabled for backward compatibility
            if (count($enabledPaylater) === 0) {
                $provider[PaylaterProvider::GETSIMPL]=true;
                $provider[PaylaterProvider::SIMPL_PAY_IN_3]=true;
            }
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

            foreach ($enabledBanks as $index => $instrument) {

                $isDisabledInstrument = in_array(strtolower($instrument), CardlessEmiProvider::$disabledInstruments, true);

                if ($isDisabledInstrument === true)
                {
                    unset($enabledBanks[$index]);
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
            foreach ($enabledBanks as $index => $instrument) {

                $isDisabledInstrument = in_array(strtolower($instrument), PaylaterProvider::$disabledInstruments, true);

                if ($isDisabledInstrument === true)
                {
                    unset($enabledBanks[$index]);
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

        if ($merchant->isLinkedAccount() === true)
        {
            return false;
        }

        if($merchant->isFeatureEnabled(FeatureConstants::OPTIMIZER_ONLY_MERCHANT) === true)
        {
            return false;
        }

        if (Environment::isEnvironmentQA($this->app['env']) || Environment::isEnvironmentItf($this->app['env']))
        {
            return false;
        }

        return true;
    }

    public function isCardPaymentMethodAllowed($merchant)
    {
        if ($merchant->getOrgId() !== OrgEntity::RAZORPAY_ORG_ID) {
            return false;
        }
        $result = 'off';
        if($this->mode == Mode::LIVE && app()->isEnvironmentProduction()){
            $result = 'on';
        }
        if ($result === 'on') {
            $this->trace->info(TraceCode::MERCHANT_ALT_ID_ONBOARDING_ENABLED, [
                'merchant_id' => $merchant->getId(),
            ]);
            return true;
        }
        return false;
    }

    public function shouldDisableUpiByDefault($merchant): bool
    {
        if ($merchant->getOrgId() !== OrgEntity::RAZORPAY_ORG_ID)
        {
            return false;
        }

        if ($merchant->isBusinessBankingEnabled() === true or $merchant->isLinkedAccount() === true)
        {
            return false;
        }

        if ($merchant->isFeatureEnabled(FeatureConstants::OPTIMIZER_ONLY_MERCHANT) === true)
        {
            return false;
        }

        return true;
    }

    /**
     * @param Entity $methods
     * @param        $input
     * @param        $mcc
     * 1. Cannot disable in_app if in_app_credit_card is enabled
     * 2. Cannot enable in_app_credit_card if in_app is not enabled
     * @return void
     */
    private function handleEnableDisableForInAppPaymentMethods(Entity $methods, $input, $mcc)
    {
        // in_app cannot be disabled if in_app subtypes are being enabled or already enabled
        if (isset($input[Entity::IN_APP]) and
            boolval($input[Entity::IN_APP]) === false)
        {
            if ($this->isInAppDisablementAllowed($methods, $input) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "in_app cannot be disabled when in_app subtypes are enabled"
                );
            }
        }

        // in_app_credit_card cannot be enabled if in_app is not being enabled and not already enabled
        if (isset($input[Entity::IN_APP_CREDIT_CARD]) and
            boolval($input[Entity::IN_APP_CREDIT_CARD]) === true)
        {
            if ($this->isInAppSubTypeEnablementAllowed($methods, $input) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "in_app_credit_card cannot be enabled if in_app is not being enabled and not already enabled"
                );
            }
        }


        // in_app_autopay cannot be enabled if in_app is not being enabled and not already enabled
        if (isset($input[Entity::IN_APP_AUTOPAY]) and
            boolval($input[Entity::IN_APP_AUTOPAY]) === true)
        {
            if ($this->isInAppSubTypeEnablementAllowed($methods, $input) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "in_app_autopay cannot be enabled if in_app is not being enabled and not already enabled"
                );
            }
        }

        // in_app_credit_line cannot be enabled if in_app is not being enabled and not already enabled
        if (isset($input[Entity::IN_APP_CREDIT_LINE]) and
            boolval($input[Entity::IN_APP_CREDIT_LINE]) === true)
        {
            if ($this->isInAppSubTypeEnablementAllowed($methods, $input) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "in_app_credit_line cannot be enabled if in_app is not being enabled and not already enabled"
                );
            }
        }

        if ((isset($input[Entity::IN_APP_CREDIT_CARD]) === true) and
            ($input[Entity::IN_APP_CREDIT_CARD] === 1))
        {
            (new Validator)->validateCategoryForInAppCreditCard($mcc);
        }
    }

    private function isInAppSubTypeBeingEnabledOrAlreadyEnabled(
        string $inAppSubType,
        Entity $methods, $input
    ): bool{
        $isInAppSubTypeBeingEnabled = (isset($input[$inAppSubType]) and
            boolval($input[$inAppSubType]) === true);

        $isInAppSubTypeAlreadyEnabled = false;
        switch ($inAppSubType)
        {
            case Entity::IN_APP_AUTOPAY:
                $isInAppSubTypeAlreadyEnabled = $methods->isInAppAutopayEnabled();
                break;
            case Entity::IN_APP_CREDIT_CARD:
                $isInAppSubTypeAlreadyEnabled = $methods->isInAppCreditCardEnabled();
                break;
            default:
                $this->trace->error(TraceCode::UNIDENTIFIED_IN_APP_SUBTYPE,[
                    'in_app_subtype', $inAppSubType,
                ]);
                break;
        }

        $isInAppSubTypeBeingDisabled = (isset($input[$inAppSubType]) and
            boolval($input[$inAppSubType]) === false);

        if ($isInAppSubTypeBeingEnabled or ($isInAppSubTypeAlreadyEnabled and !$isInAppSubTypeBeingDisabled))
        {
            return true;
        }

        return false;
    }

    private function isInAppDisablementAllowed(Entity $methods, $input): bool
    {
        if (
            $this->isInAppSubTypeBeingEnabledOrAlreadyEnabled(
                Entity::IN_APP_AUTOPAY, $methods, $input) or
            $this->isInAppSubTypeBeingEnabledOrAlreadyEnabled(
                Entity::IN_APP_CREDIT_CARD, $methods, $input) or
            $this->isInAppSubTypeBeingEnabledOrAlreadyEnabled(
                Entity::IN_APP_CREDIT_LINE, $methods, $input)
        )
        {
            return false;
        }

        return true;
    }

    private function isInAppSubTypeEnablementAllowed(Entity $methods, $input): bool
    {
        $isInAppAlreadyDisabled = $methods->isInAppEnabled() === false;
        $isInAppNotBeingEnabled = (
            !isset($input[Entity::IN_APP]) or
            (isset($input[Entity::IN_APP]) and boolval($input[Entity::IN_APP]) != true)
        );

        // in_app already enabled but being disabled case is covered in
        // earlier step where we check is_in_app_disablement is allowed.
        if ($isInAppAlreadyDisabled and $isInAppNotBeingEnabled)
        {
            return false;
        }

        return true;
    }

    /**
     * If default_payment_methods in partner config table is set for the first connected partner app of the sub-merchant,
     * then we will skip subsequent steps to reset payment methods, considering that the merchant would have picked
     * default payment methods from partner config during merchant_banks entry creation.
     */
    private function isSkipMethodResetForSubmerchant(string $merchantId): bool
    {
        $this->trace->info(
            TraceCode::MERCHANT_METHODS_RESET_BASED_ON_CATEGORY_REQUEST,
            [
                'merchant_id'                         => $merchantId,
                'DEFAULT_PARTNER_PAYMENT_METHODS_SET_CHECK' => true
            ]
        );

        $applicationId =  $this->repo->merchant_access_map->fetchEntityIdsForSubmerchant($merchantId, true)->first();

        $defaultPaymentMethods = $this->getDefaultPaymentMethodsForPartnerApplication($applicationId);

        if (empty($defaultPaymentMethods) === false)
        {
            $this->trace->info(
                TraceCode::MERCHANT_METHODS_RESET_BASED_ON_CATEGORY_REQUEST,
                [
                    'merchant_id'                         => $merchantId,
                    'DEFAULT_PARTNER_PAYMENT_METHODS_SET' => true,
                    'partner_app_id'                      => $applicationId
                ]
            );
            return true;
        }
        return false;
    }
}
