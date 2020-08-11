<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccount\Channel;

class Validator extends Base\Validator
{
    // Types of Business Categories
    const PRIVATE_PUBLIC_LIMITED_COMPANY = 'private_public_limited_company';
    const SOLE_PROPRIETORSHIP = 'sole_proprietorship';
    const LIMITED_LIABILITY_PARTNERSHIP = 'limited_liability_partnership';
    const PARTNERSHIP = 'partnership';

    // Types of Accounts for RBL
    const INSIGNIA = 'insignia';
    const PREMIUM = 'premium';
    const BUSINESS_PLUS = 'business_plus';
    const ZERO_BALANCE = 'zero_balance';

    // Types of Sales teams
    const GROWTH = 'growth';
    const DIRECT_SALES = 'direct_sales';
    const KEY_ACCOUNT = 'key_account';
    const SME = 'sme';

    protected static $createRules = [
        Entity::BANKING_ACCOUNT_ID                  => 'required|string|size:14',
        Entity::MERCHANT_POC_NAME                   => 'required|string|max:255',
        Entity::MERCHANT_POC_DESIGNATION            => 'required|string|max:255',
        Entity::MERCHANT_POC_EMAIL                  => 'required|string|max:255',
        Entity::MERCHANT_POC_PHONE_NUMBER           => 'required|string|max:255',
        Entity::MERCHANT_CITY                       => 'required|string|max:255',
        Entity::MERCHANT_DOCUMENTS_ADDRESS          => 'required|string|max:255',
        Entity::MERCHANT_REGION                     => 'required|string|max:255',
        Entity::EXPECTED_MONTHLY_GMV                => 'required|integer|min:0',
        Entity::INITIAL_CHEQUE_VALUE                => 'required|integer|min:0',
        Entity::BUSINESS_CATEGORY                   => 'required|string|max:255|custom',
        Entity::AVERAGE_MONTHLY_BALANCE             => 'required|integer|min:0',
        Entity::ACCOUNT_TYPE                        => 'required|string|max:255',
        Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE   => 'required|boolean',
        Entity::SALES_TEAM                          => 'required|string|max:255|custom',
        Entity::SALES_POC_PHONE_NUMBER              => 'required|string|max:255',
        Entity::COMMENT                             => 'required|string'
    ];

    protected static $editRules = [
        Entity::MERCHANT_POC_NAME                   => 'sometimes|string|max:255',
        Entity::MERCHANT_POC_DESIGNATION            => 'sometimes|string|max:255',
        Entity::MERCHANT_POC_EMAIL                  => 'sometimes|string|max:255',
        Entity::MERCHANT_POC_PHONE_NUMBER           => 'sometimes|string|max:255',
        Entity::MERCHANT_CITY                       => 'sometimes|string|max:255',
        Entity::MERCHANT_DOCUMENTS_ADDRESS          => 'sometimes|string|max:255',
        Entity::MERCHANT_REGION                     => 'sometimes|string|max:255',
        Entity::EXPECTED_MONTHLY_GMV                => 'sometimes|integer|min:0',
        Entity::INITIAL_CHEQUE_VALUE                => 'sometimes|integer|min:0',
        Entity::BUSINESS_CATEGORY                   => 'sometimes|string|max:255|custom',
        Entity::AVERAGE_MONTHLY_BALANCE             => 'sometimes|integer|min:0',
        Entity::ACCOUNT_TYPE                        => 'sometimes|string|max:255',
        Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE   => 'sometimes|boolean',
        Entity::SALES_TEAM                          => 'sometimes|string|max:255|custom',
        Entity::SALES_POC_PHONE_NUMBER              => 'sometimes|string|max:255',
    ];

    protected static $allowedBusinessCategories = [
        self::PRIVATE_PUBLIC_LIMITED_COMPANY,
        self::SOLE_PROPRIETORSHIP,
        self::LIMITED_LIABILITY_PARTNERSHIP,
        self::PARTNERSHIP
    ];

    protected static $allowedAccountTypesForRBL = [
        self::INSIGNIA,
        self::PREMIUM,
        self::BUSINESS_PLUS,
        self::ZERO_BALANCE
    ];

    protected static $allowedSalesTeams = [
        self::DIRECT_SALES,
        self::GROWTH,
        self::KEY_ACCOUNT,
        self::SME
    ];

    public function validateBusinessCategory($attribute, $value)
    {
        if (in_array($value, self::$allowedBusinessCategories) === false)
        {
            throw new BadRequestValidationFailureException(
                'The business category field is invalid.',
                Entity::BUSINESS_CATEGORY);
        }
    }

    public function validateSalesTeam($attribute, $value)
    {
        if (in_array($value, self::$allowedSalesTeams) === false)
        {
            throw new BadRequestValidationFailureException(
                'The Sales team field is invalid.',
                Entity::SALES_TEAM);
        }
    }

    public function validateAccountTypeForChannel(\RZP\Models\BankingAccount\Entity $bankingAccount, $input)
    {
        if ((isset($input[Entity::ACCOUNT_TYPE])) and ($bankingAccount->getChannel() === Channel::RBL))
        {
            if (in_array($input[Entity::ACCOUNT_TYPE], self::$allowedAccountTypesForRBL) === false)
            {
                throw new BadRequestValidationFailureException(
                    'The account type field is invalid.',
                    Entity::BUSINESS_CATEGORY);
            }
        }
    }

}
