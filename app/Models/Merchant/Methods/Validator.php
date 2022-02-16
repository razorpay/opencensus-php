<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment\Processor\Netbanking;

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

        Entity::DEBIT_EMI_PROVIDERS      => 'sometimes|array',
        Entity::DEBIT_EMI_PROVIDERS.'.*' => 'sometimes|boolean',
        Entity::ADDITIONAL_WALLETS => 'sometimes|array',
        Entity::ITZCASH            => 'sometimes|boolean',
        Entity::OXIGEN             => 'sometimes|boolean',
        Entity::AMEXEASYCLICK      => 'sometimes|boolean',
        Entity::PAYCASH            => 'sometimes|boolean',
        Entity::CITIBANKREWARDS    => 'sometimes|boolean',
        Entity::COD                => 'sometimes|boolean',
        Entity::OFFLINE            => 'sometimes|boolean',
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
}
