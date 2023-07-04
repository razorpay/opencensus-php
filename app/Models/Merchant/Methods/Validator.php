<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Trace\TraceCode;

class Validator extends Base\Validator
{
    protected static $addDisabledBanksRules = [
        Entity::DISABLED_BANKS => 'sometimes|array'
    ];

    protected static $addDisabledBanksValidators = [
        Entity::DISABLED_BANKS
    ];

    protected static $setMethodsRules = [
        Entity::ENABLED_BANKS      => 'sometimes|array',
        Entity::DISABLED_BANKS     => 'sometimes|array',
        Entity::NETBANKING         => 'sometimes|boolean',
        Entity::AMEX               => 'sometimes|boolean',
        Entity::PAYTM              => 'sometimes|boolean',
        Entity::PAYZAPP            => 'sometimes|boolean',
        Entity::PAYUMONEY          => 'sometimes|boolean',
        Entity::AIRTELMONEY        => 'sometimes|boolean',
        Entity::AMAZONPAY          => 'sometimes|boolean',
        Entity::OPENWALLET         => 'sometimes|boolean',
        Entity::RAZORPAYWALLET     => 'sometimes|boolean',
        Entity::OLAMONEY           => 'sometimes|boolean',
        Entity::PHONEPE            => 'sometimes|boolean',
        Entity::PHONEPE_SWITCH     => 'sometimes|boolean',
        Entity::PAYPAL             => 'sometimes|boolean',
        Entity::MOBIKWIK           => 'sometimes|boolean',
        Entity::FREECHARGE         => 'sometimes|boolean',
        Entity::JIOMONEY           => 'sometimes|boolean',
        Entity::SBIBUDDY           => 'sometimes|boolean',
        Entity::EMI                => 'sometimes|array',
        Entity::CREDIT_CARD        => 'sometimes|boolean',
        Entity::DEBIT_CARD         => 'sometimes|boolean',
        Entity::CARD_SUBTYPE       => 'sometimes|array',
        Entity::CARD_SUBTYPE.'.*'  => 'sometimes|boolean',
        Entity::PREPAID_CARD       => 'sometimes|boolean',
        Entity::UPI                => 'sometimes|boolean',
        Entity::UPI_TYPE           => 'sometimes|array',
        Entity::UPI_TYPE.'.*'      => 'sometimes|boolean',
        Entity::AEPS               => 'sometimes|boolean',
        Entity::EMANDATE           => 'sometimes|boolean',
        Entity::NACH               => 'sometimes|boolean',
        Entity::MPESA              => 'sometimes|boolean',
        Entity::BANK_TRANSFER      => 'sometimes|boolean',
        Entity::CARDLESS_EMI       => 'sometimes|boolean',
        Entity::PAYLATER           => 'sometimes|boolean',
        Entity::CARD_NETWORKS      => 'sometimes|array',
        Entity::CARD_NETWORKS.'.*' => 'sometimes|boolean',
        Entity::APPS               => 'sometimes|array',
        Entity::APPS.'.*'          => 'sometimes|boolean',

        Entity::DEBIT_EMI_PROVIDERS         => 'sometimes|array',
        Entity::DEBIT_EMI_PROVIDERS.'.*'    => 'sometimes|boolean',
        Entity::CREDIT_EMI_PROVIDERS        => 'sometimes|array',
        Entity::CREDIT_EMI_PROVIDERS.'.*'   => 'sometimes|boolean',
        Entity::CARDLESS_EMI_PROVIDERS      => 'sometimes|array',
        Entity::CARDLESS_EMI_PROVIDERS.'.*' => 'sometimes|boolean',
        Entity::PAYLATER_PROVIDERS          => 'sometimes|array',
        Entity::PAYLATER_PROVIDERS.'.*'     => 'sometimes|boolean',
        Entity::ADDITIONAL_WALLETS => 'sometimes|array',
        Entity::ITZCASH            => 'sometimes|boolean',
        Entity::OXIGEN             => 'sometimes|boolean',
        Entity::AMEXEASYCLICK      => 'sometimes|boolean',
        Entity::PAYCASH            => 'sometimes|boolean',
        Entity::CITIBANKREWARDS    => 'sometimes|boolean',
        Entity::COD                => 'sometimes|boolean',
        Entity::OFFLINE            => 'sometimes|boolean',
        Entity::FPX                => 'sometimes|boolean',
        Entity::ADDON_METHODS      => 'sometimes|array',
        Entity::IN_APP             => 'sometimes|boolean',
        Entity::BAJAJPAY           => 'sometimes|boolean',
        Entity::MCASH              => 'sometimes|boolean',
        Entity::GRABPAY            => 'sometimes|boolean',
        Entity::TOUCHNGO           => 'sometimes|boolean',
        Entity::BOOST              => 'sometimes|boolean',
        Entity::INTL_BANK_TRANSFER => 'sometimes|array',
        Entity::INTL_BANK_TRANSFER.'.*' => 'sometimes|boolean',
        Entity::SODEXO             => 'sometimes|boolean',
    ];

    protected static $setMethodsValidators = [
        'methodBanks',
        'methodUpi'
    ];

    protected static $bulkAssignMethodsRules = [
        'methods'                    => 'required|array|custom',
        'merchants'                  => 'required|array',
        'merchants.*'                => 'required|string|filled|size:14'
    ];

    protected static $emiBlacklistedCategories = [
        '5094',
        '5944',
        '7631'
    ];

    protected function validateMethodBanks(array $input)
    {
        if (isset($input['disabled_banks']) === false)
        {
            return;
        }

        $this->validateDisabledBanks($input);
    }

    protected function validateMethodUpi(array $input)
    {
        if (isset($input['upi']) === false || isset($input['upi_type']) === false)
        {
            return;
        }

        $this->validateUpi($input);
    }

    protected function validateMethods($attribute, $methods, $parameters)
    {
        $this->validateInput('set_methods', $methods);
    }

    protected function validateDisabledBanks(array $input)
    {
        if (is_array($input['disabled_banks']) === false)
        {
            throw new Exception\LogicException(
                'Not an array',
                null,
                [
                    'banks' => $input['disabled_banks'],
                ]);
        }

        $banks = $input['disabled_banks'];

        $unsupported = Netbanking::findUnsupportedBanks($banks);

        if (count($unsupported) !== 0)
        {
            $msg = implode(', ', $unsupported) . ' are either invalid or unsupported banks';

            throw new Exception\BadRequestValidationFailureException(
                $msg, 'banks');
        }

        $uniqBanks = array_unique($banks);

        if (count($banks) !== count($uniqBanks))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Some banks are repeated',
                'banks');
        }
    }

    protected function validateUpi(array $input)
    {
        if (isset($input['upi']) === true && isset($input['upi_type']) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Upi and upi_type cannot be set at the same time',
                'upi');
        }
    }

    public function validateCategoryForEmi(string $mcc)
    {
        if (in_array($mcc, self::$emiBlacklistedCategories)) {
            throw new Exception\BadRequestValidationFailureException(
                'EMI cannot be enabled for this MCC: '.$mcc,
                'emi');
        }
    }

    public function validateCategoryForAmexCardNetwork(string $mcc)
    {
        if (in_array($mcc, DefaultMethodsForCategory::AMEX_BLACKLISTED_MCCS)) {
            throw new Exception\BadRequestValidationFailureException(
                'AMEX card network cannot be enabled for this MCC: '.$mcc,
                'card_networks');
        }
    }

    public function validateEmiOptionsForJewelleryMerchants(string $categoryToBeUpdated, \RZP\Models\Merchant\Entity $merchant)
    {
        $methods = (new Core)->getMethods($merchant);
        $emiTypes = $methods->getEmiTypes();

        if(in_array($categoryToBeUpdated, self::$emiBlacklistedCategories) && ($emiTypes['credit'] === true || $emiTypes['debit'] === true)){
            throw new Exception\BadRequestValidationFailureException(
                'Please disable the EMI in order to update the Category');
            }
        }

    public function validateAndAllowResetMerchantMethods(string $merchantId)
    {
        $blockMethodsReset = (new Core)->validateRuleBasedFeatureFlagForMerchant($merchantId);

        if ($blockMethodsReset)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please disable "rule_based_enablement" feature to reset all methods. Or please try MCC edit without
                resetting merchant methods' );
        }
    }

    public function validateCategoryForPaylater(string $mcc)
    {
        if (in_array($mcc, DefaultMethodsForCategory::PAYLATER_DISABLED_MCCS)) {
            throw new Exception\BadRequestValidationFailureException(
                'Paylater cannot be enabled for this MCC: '.$mcc);
        }
    }
    public function validateCategoryForAmazonPay(string $mcc)
    {
        if (in_array($mcc, DefaultMethodsForCategory::AMAZONPAY_DISABLED_MCCS)) {
            throw new Exception\BadRequestValidationFailureException(
                'AmazonPay cannot be enabled for this MCC: '.$mcc);
        }
    }
}
