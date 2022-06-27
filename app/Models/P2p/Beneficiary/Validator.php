<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\BankAccount;
use RZP\Models\P2p\Transaction;

class Validator extends Base\Validator
{
    protected static $addRules;
    protected static $validateRules;
    protected static $validateSuccessRules;
    protected static $fetchAllRules;
    protected static $handleBeneficiaryRules;
    protected static $handleBeneficiarySuccessRules;
    protected static $getBlockedRules;

    public function rules()
    {
        $rules = [
            Entity::DEVICE_ID    => 'string',
            Entity::ENTITY_TYPE  => 'string',
            Entity::ENTITY_ID    => 'string',
            Entity::NAME         => 'string',
            Entity::VALIDATED    => 'boolean',
            Entity::TYPE         => 'string|in:vpa,bank_account',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::DEVICE_ID    => 'sometimes',
            Entity::ENTITY_TYPE  => 'sometimes',
            Entity::ENTITY_ID    => 'sometimes',
            Entity::NAME         => 'sometimes',
        ]);

        return $rules;
    }

    public function makeAddRules()
    {
        $rules = $this->makeRules([
            Entity::TYPE        => 'required',
            Entity::ID          => 'required',
            Entity::VALIDATED   => 'sometimes',
        ]);

        return $rules;
    }

    public function makeValidateRules()
    {
        $rules = $this->makeRules([
            Entity::TYPE            => 'required',
        ]);

        $rules->merge((new Vpa\Validator)->makeRules([
            Vpa\Entity::HANDLE      => 'required_if:type,vpa',
            Vpa\Entity::USERNAME    => 'required_if:type,vpa',
            Vpa\Entity::VERIFIED    => 'sometimes_if:type,vpa',
        ]));

        $rules->merge((new BankAccount\Validator)->makeRules([
            BankAccount\Entity::ACCOUNT_NUMBER      => 'required_if:type,bank_account',
            BankAccount\Entity::IFSC                => 'required_if:type,bank_account',
            BankAccount\Entity::BENEFICIARY_NAME    => 'required_if:type,bank_account',
        ]));

        return $rules;
    }

    public function makeValidateSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::TYPE            => 'required',
            Entity::GATEWAY_DATA    => 'sometimes',
            Entity::VALIDATED       => 'sometimes',
        ]);

        $rules->merge((new Vpa\Validator)->makeRules([
            Vpa\Entity::HANDLE                  => 'required_if:type,vpa',
            Vpa\Entity::USERNAME                => 'required_if:type,vpa',
            Vpa\Entity::BENEFICIARY_NAME        => 'required_if:type,vpa',
            Vpa\Entity::VERIFIED                => 'sometimes_if:type,vpa',
        ]));

        $rules->merge((new BankAccount\Validator)->makeRules([
            BankAccount\Entity::ACCOUNT_NUMBER      => 'required_if:type,bank_account',
            BankAccount\Entity::IFSC                => 'required_if:type,bank_account',
            BankAccount\Entity::BENEFICIARY_NAME    => 'required_if:type,bank_account',
        ]));

        return $rules;
    }

    public function makeHandleBeneficiaryRules()
    {
        $rules = $this->makeRules([
            Vpa\Entity::USERNAME    => 'required',
            Vpa\Entity::HANDLE      => 'required',
            Entity::TYPE            => 'required|in:vpa',
            Entity::SPAMMED         => 'required',
            Entity::BLOCKED         => 'required',
            Transaction\Entity::UPI => 'sometimes|array',
        ]);

        return $rules;
    }

    public function makeHandleBeneficiarySuccessRules()
    {
        $rules = $this->makeRules([
            Vpa\Entity::USERNAME => 'required',
            Vpa\Entity::HANDLE   => 'required',
            Entity::SPAMMED      => 'required',
            Entity::BLOCKED      => 'required',
            Entity::TYPE         => 'required|in:vpa',
            Entity::BLOCKED_AT   => 'sometimes|nullable|integer',
        ]);

        return $rules;
    }

    public function makeFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeGetBlockedRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
