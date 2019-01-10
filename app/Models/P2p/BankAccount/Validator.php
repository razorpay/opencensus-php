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

    public function rules()
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

    public function makeCreateRules()
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

    public function makeFetchBanksRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeRetrieveRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeFetchRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeInitiateSetUpiPinRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeSetUpiPinRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeInitiateFetchBalanceRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeFetchBalanceRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
