<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Upi\Txn;
use RZP\Models\P2p\Base\Libraries\Card;

class Validator extends Base\Validator
{
    protected static $editRules;
    protected static $beneficiaryRules;
    protected static $fetchBanksRules;
    protected static $initiateRetrieveRules;
    protected static $retrieveRules;
    protected static $retrieveSuccessRules;
    protected static $fetchAllRules;
    protected static $fetchRules;
    protected static $initiateSetUpiPinRules;
    protected static $initiateSetUpiPinSuccessRules;
    protected static $setUpiPinRules;
    protected static $setUpiPinSuccessRules;
    protected static $initiateFetchBalanceRules;
    protected static $initiateFetchBalanceSuccessRules;
    protected static $fetchBalanceRules;
    protected static $fetchBalanceSuccessRules;

    public function rules()
    {
        $rules = [
            Entity::DEVICE_ID             => 'string',
            Entity::HANDLE                => 'string',
            Entity::GATEWAY_DATA          => 'array',
            Entity::BANK_ID               => 'string',
            Entity::IFSC                  => 'string|regex:/^[A-Za-z0-9]{11}$/',
            Entity::ACCOUNT_NUMBER        => 'string',
            Entity::MASKED_ACCOUNT_NUMBER => 'string',
            Entity::BENEFICIARY_NAME      => 'string',
            Entity::CREDS                 => 'array',
            Entity::TYPE                  => 'string',
        ];

        return $rules;
    }

    public function makeGatewayDataRules()
    {
        $rules = $this->makeRules();

        $rules->arrayRules(Entity::GATEWAY_DATA,
            [
                Entity::ID  => 'required|string',
            ]);

        return $rules;
    }

    public function makeCredsRules()
    {
        $rules = $this->makeRules();

        $credRules = Credentials::rules()->with([
            Credentials::TYPE           => 'required',
            Credentials::SUB_TYPE       => 'required',
        ]);

        $rules->arrayRules(Credentials::CREDS, $credRules->toArray(), true);

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::GATEWAY_DATA             => 'sometimes',
            Entity::IFSC                     => 'required',
            Entity::ACCOUNT_NUMBER           => 'sometimes',
            Entity::MASKED_ACCOUNT_NUMBER    => 'required',
            Entity::BENEFICIARY_NAME         => 'sometimes',
            Entity::CREDS                    => 'required',
            Entity::TYPE                     => 'sometimes',
        ]);

        $rules->merge($this->makeGatewayDataRules());
        $rules->merge($this->makeCredsRules());

        return $rules;
    }

    public function makeEditRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeBeneficiaryRules()
    {
        $rules = $this->makeRules([
            Entity::IFSC                     => 'required',
            Entity::ACCOUNT_NUMBER           => 'required',
            Entity::BENEFICIARY_NAME         => 'required',
        ]);

        return $rules;
    }

    public function makeFetchBanksRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeInitiateRetrieveRules()
    {
        $rules = $this->makeRules([
            Entity::BANK_ID => 'required',
        ]);

        return $rules;
    }

    public function makeRetrieveRules()
    {
        $rules = $this->makeRules([
            Entity::BANK_ID => 'required',
        ]);

        return $rules;
    }

    public function makeRetrieveSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::BANK_ID => 'required',
        ]);

        $rules->arrayRules(Entity::BANK_ACCOUNTS,
                           $this->makeCreateRules()->toArray(),
                           true);

        return $rules;
    }

    public function makeInitiateSetUpiPinRules()
    {
        $rules = $this->makePublicIdRules();

        $rules->merge(Credentials::actionRules());

        return $rules;
    }

    public function makeInitiateSetUpiPinSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::BANK_ID => 'required',
        ]);

        $rules->arrayRules(Entity::BANK_ACCOUNT, [
            Entity::ID          => 'required|string'
        ]);

        return $rules;
    }

    public function makeSetUpiPinRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }

    public function makeSetUpiPinSuccessRules()
    {
        $rules = $this->makeEntityIdRules();

        return $rules;
    }

    public function makeInitiateFetchBalanceRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }

    public function makeInitiateFetchBalanceSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::BANK_ID => 'required',
        ]);

        $rules->arrayRules(Entity::BANK_ACCOUNT, [
            Entity::ID          => 'required|string'
        ]);

        return $rules;
    }

    public function makeFetchBalanceRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }

    public function makeFetchBalanceSuccessRules()
    {
        $rules = $this->makeEntityIdRules();

        $rules->arrayRules(Entity::RESPONSE, [
            Entity::BALANCE     => 'required|integer',
            Entity::CURRENCY    => 'required|in:INR',
        ]);

        return $rules;
    }
}
