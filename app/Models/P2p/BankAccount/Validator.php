<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Upi\Txn;
use RZP\Models\P2p\Base\Libraries\Card;

class Validator extends Base\Validator
{
    protected static $fetchBanksRules;
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
            Credentials::FORMAT         => 'required',
            Credentials::LENGTH         => 'required',
        ]);

        $rules->arrayRules(Credentials::CREDS, $credRules->toArray(), true);

        return $rules;
    }

    public function makeCredBlockRules()
    {
        $rules = $this->makeRules();

        $credRules = Credentials::rules()->with([
            Credentials::TYPE           => 'required',
            Credentials::SUB_TYPE       => 'required',
            Credentials::STRING         => 'required',
            Credentials::CODE           => 'required',
            Credentials::KI             => 'required',
        ]);

        $rules->arrayRules(Credentials::CREDS, $credRules->toArray(), true);

        return $rules;
    }

    public function makeCardRules()
    {
        $rules = $this->makeRules();

        $cardRules = Card::rules()->with([
            Card::EXPIRY_YEAR       => 'required',
            Card::EXPIRY_MONTH      => 'required',
            Card::LAST6             => 'required',
        ]);

        $rules->arrayRules(Card::CARD, $cardRules->toArray());

        return $rules;
    }

    public function makeTxnRules()
    {
        $rules = $this->makeRules();

        $txnRules = Txn::rules()->with([
            Txn::ID     => 'required',
        ]);

        $rules->arrayRules(Txn::TXN, $txnRules->toArray());

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
        ]);

        $rules->merge($this->makeGatewayDataRules());
        $rules->merge($this->makeCredsRules());

        return $rules;
    }

    public function makeFetchBanksRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeRetrieveRules()
    {
        $rules = $this->makeRules([
            Entity::BANK        => 'required',
        ]);

        return $rules;
    }

    public function makeRetrieveSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::BANK    => 'required',
        ]);

        $rules->arrayRules(Entity::BANK_ACCOUNTS,
                           $this->makeCreateRules()->toArray(),
                           true);

        return $rules;
    }

    public function makeFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeFetchRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }

    public function makeInitiateSetUpiPinRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }

    public function makeInitiateSetUpiPinSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::BANK        => 'required',
        ]);

        $rules->arrayRules(Entity::BANK_ACCOUNT, [
            Entity::ID          => 'required|string'
        ]);

        $rules->merge($this->makeTxnRules());

        return $rules;
    }

    public function makeSetUpiPinRules()
    {
        $rules = $this->makePublicIdRules();

        $rules->arrayRules(Entity::CL, $this->makeCredBlockRules()->toArray());
        $rules->merge($this->makeCardRules());
        $rules->merge($this->makeTxnRules());

        return $rules;
    }

    public function makeSetUpiPinSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::BANK        => 'required',
        ]);

        $rules->arrayRules(Entity::BANK_ACCOUNT, [
            Entity::ID          => 'required|string'
        ]);

        $rules->merge($this->makeTxnRules());

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
            Entity::BANK        => 'required',
        ]);

        $rules->arrayRules(Entity::BANK_ACCOUNT, [
            Entity::ID          => 'required|string'
        ]);

        $rules->merge($this->makeTxnRules());

        return $rules;
    }

    public function makeFetchBalanceRules()
    {
        $rules = $this->makePublicIdRules();

        $rules->arrayRules(Entity::CL, $this->makeCredBlockRules()->toArray());
        $rules->merge($this->makeTxnRules());

        return $rules;
    }

    public function makeFetchBalanceSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::BANK        => 'required',
        ]);

        $rules->arrayRules(Entity::BANK_ACCOUNT, [
            Entity::ID          => 'required|string'
        ]);

        $rules->merge($this->makeTxnRules());

        $rules->arrayRules(Entity::RESPONSE, [
            Entity::BALANCE     => 'required|integer',
            Entity::CURRENCY    => 'required|in:INR',
        ]);

        return $rules;
    }
}
