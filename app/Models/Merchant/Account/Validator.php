<?php

namespace RZP\Models\Merchant\Account;

use RZP\Base\Fetch;
use RZP\Models\Merchant;

class Validator extends Merchant\Validator
{
    protected static $createRules = [
         Entity::NAME                                          => 'required|alpha_space_num|max:200',
         Entity::EMAIL                                         => 'required|email',
         Entity::TNC_ACCEPTED                                  => 'required|boolean|in:1',
         Entity::NOTES                                         => 'sometimes|array|max:15',
         Entity::ACCOUNT_DETAILS                               => 'required|array',
         Entity::ACCOUNT_DETAILS . '.' . Entity::BUSINESS_NAME => 'required|string|max:255',
         Entity::ACCOUNT_DETAILS . '.' . Entity::BUSINESS_TYPE => 'required|string|max:100',

         // For following only key presence is validated here.
         // Sub keys are validated in respective validators.
         Entity::BANK_ACCOUNT                                  => 'required|array',
     ];

    protected static $fetchRules = [
        EsRepository::SEARCH_HITS => 'filled|boolean',
        EsRepository::QUERY       => 'filled|string|min:2|max:100',
        Entity::EMAIL             => 'sometimes|email',
        Fetch::SKIP               => 'sometimes|integer',
        Fetch::COUNT              => 'sometimes|integer',
        Entity::ID                => 'sometimes|string|min:14',
    ];

    protected static $createAccountRules = [
        Constants::ENTITY          => 'required|string',
        Constants::BUSINESS_ENTITY => 'sometimes|string',
        Constants::MANAGED         => 'sometimes|boolean',
        Constants::EMAIL           => 'required|email',
        Constants::PHONE           => 'required|numeric|digits_between:8,11',
        Constants::NOTES           => 'sometimes|notes',
        Constants::PROFILE         => 'required|array',
        Constants::SETTLEMENT      => 'sometimes|array',
        Constants::TNC             => 'sometimes|array',
    ];

    protected static $profileRules = [
        Constants::ADDRESSES         => 'required|array|max:2',
        Constants::NAME              => 'required|string',
        Constants::DESCRIPTION       => 'sometimes|string',
        Constants::BUSINESS_MODEL    => 'sometimes|string',
        Constants::MCC               => 'required|numeric',
        Constants::BRAND             => 'sometimes|array',
        Constants::DASHBOARD_DISPLAY => 'sometimes|string',
        Constants::WEBSITE           => 'sometimes|string',
        Constants::APPS              => 'sometimes|array',
        Constants::SUPPORT           => 'sometimes|array',
        Constants::CHARGEBACK        => 'sometimes|array',
        Constants::REFUND            => 'sometimes|array',
        Constants::DISPUTE           => 'sometimes|array',
        Constants::BILLING_LABEL     => 'sometimes|string',
    ];

    protected static $accountAddressRules = [
        Constants::TYPE    => 'required|string|in:' . Constants::REGISTERED . ',' . Constants::OPERATION,
        Constants::LINE1   => 'required|string',
        Constants::LINE2   => 'required|string',
        Constants::CITY    => 'required|string',
        Constants::STATE   => 'required|string',
        Constants::PIN     => 'required|string',
        Constants::COUNTRY => 'required|string',
    ];

    protected static $brandRules = [
        Constants::ICON  => 'sometimes|string',
        Constants::LOGO  => 'sometimes|string',
        Constants::COLOR => 'sometimes|string',
    ];

    protected static $emailRules = [
        Constants::EMAIL  => 'required|string',
        Constants::PHONE  => 'required|numeric|digits_between:8,11',
        Constants::POLICY => 'sometimes|string',
        Constants::URL    => 'sometimes|string',
    ];

    protected static $settlementRules = [
        Constants::BALANCE_RESERVED => 'sometimes|numeric',
        Constants::SCHEDULES        => 'sometimes|array',
        Constants::FUND_ACCOUNTS    => 'sometimes|array|size:1',
    ];

    protected static $fundAccountRules = [
        Constants::CONTACT_ID    => 'sometimes|string',
        Constants::BANK_ACCOUNT  => 'required|associative_array',
    ];

    protected static $bankAccountRules = [
        Constants::IFSC           => 'required|string',
        Constants::NAME           => 'required|string',
        Constants::ACCOUNT_NUMBER => 'required|string',
    ];

    protected static $listAccountsRules = [
        Merchant\Constants::COUNT => 'required|integer|min:1|max:30',
        Merchant\Constants::SKIP  => 'integer',
    ];

    protected static $createAccountValidators = [
        'profile_input',
        'settlement_input',
    ];

    protected function validateProfileInput(array $input)
    {
        $this->validateInput('profile', $input[Constants::PROFILE]);

        foreach ($input[Constants::PROFILE][Constants::ADDRESSES] as $address)
        {
            $this->validateInput('account_address', $address);
        }

        if (isset($input[Constants::PROFILE][Constants::BRAND]) === true)
        {
            $this->validateInput('brand', $input[Constants::PROFILE][Constants::BRAND]);
        }

        $fieldNames = [
            Constants::SUPPORT,
            Constants::CHARGEBACK,
            Constants::REFUND,
            Constants::DISPUTE,
        ];

        foreach ($fieldNames as $fieldName)
        {
            if (isset($input[Constants::PROFILE][$fieldName]) === true)
            {
                $this->validateInput('email', $input[Constants::PROFILE][$fieldName]);
            }
        }
    }

    protected function validateSettlementInput(array $input)
    {
        if (isset($input[Constants::SETTLEMENT]) === false)
        {
            return;
        }

        $this->validateInput('settlement', $input[Constants::SETTLEMENT]);

        if (isset($input[Constants::SETTLEMENT][Constants::FUND_ACCOUNTS]) === true)
        {
            $fundAccounts = $input[Constants::SETTLEMENT][Constants::FUND_ACCOUNTS];

            foreach ($fundAccounts as $fundAccount)
            {
                $this->validateInput('fund_account', $fundAccount);
                $this->validateInput('bank_account', $fundAccount[Constants::BANK_ACCOUNT]);
            }
        }
    }
}
