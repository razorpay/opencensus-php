<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $fetchBanksRules;
    protected static $retrieveRules;
    protected static $fetchAllRules;
    protected static $fetchRules;
    protected static $initiateSetUpiPinRules;
    protected static $setUpiPinRules;
    protected static $initiateFetchBalanceRules;
    protected static $fetchBalanceRules;

    protected function rules()
    {
        $rules = [
            Entity::DEVICE_ID                => 'string',
            Entity::HANDLE                   => 'string',
            Entity::GATEWAY_DATA             => 'array',
            Entity::BANK                     => 'string',
            Entity::IFSC                     => 'string',
            Entity::ACCOUNT_NUMBER           => 'string',
            Entity::MASKED_ACCOUNT_NUMBER    => 'string',
            Entity::BENEFICIARY_NAME         => 'string',
            Entity::CREDS                    => 'array',
        ];

        return $rules;
    }

    protected function getCreateRules()
    {
        $rules = $this->makeRules([
            Entity::DEVICE_ID                => 'sometimes',
            Entity::HANDLE                   => 'sometimes',
            Entity::GATEWAY_DATA             => 'sometimes',
            Entity::BANK                     => 'sometimes',
            Entity::IFSC                     => 'sometimes',
            Entity::ACCOUNT_NUMBER           => 'sometimes',
            Entity::MASKED_ACCOUNT_NUMBER    => 'sometimes',
            Entity::BENEFICIARY_NAME         => 'sometimes',
            Entity::CREDS                    => 'sometimes',
        ]);

        return $rules;
    }

    protected function getFetchBanksRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getRetrieveRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getFetchRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getInitiateSetUpiPinRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getSetUpiPinRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getInitiateFetchBalanceRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getFetchBalanceRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
