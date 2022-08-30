<?php

namespace RZP\Models\P2p\BlackList;


use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\BankAccount;

/**
 * Class Validator
 * @package RZP\Models\P2p\BlackList
 */
class Validator extends Base\Validator
{
    protected static $addBlacklistRules;
    protected static $removeBlacklistRules;

    public function rules()
    {
        $rules = [
            Entity::TYPE => 'string | in:vpa,bank_account',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::ENTITY_ID                       => 'sometimes',
            Entity::CLIENT_ID                       => 'sometimes',
            Entity::TYPE                            => 'sometimes',
        ]);
        return $rules;
    }

    public function makeRemoveBlacklistRules()
    {
        $rules = $this->makeRules([
              Entity::ENTITY_ID                       => 'sometimes',
              Entity::CLIENT_ID                       => 'sometimes',
              Entity::TYPE                            => 'sometimes',
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

    public function makeAddBlacklistRules()
    {
        $rules = $this->makeRules([
            Entity::ENTITY_ID                     => 'sometimes',
            Entity::CLIENT_ID                     => 'sometimes',
            Entity::TYPE                          => 'sometimes',
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

    public function makeValidateRules()
    {
        $rules = $this->makeRules([
            Entity::TYPE        => 'required',
            Entity::ENTITY_ID   => 'required',
            Entity::CLIENT_ID   => 'required',
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
}
