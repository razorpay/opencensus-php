<?php

namespace RZP\Models\P2p\BankAccount\Bank;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Libraries\Context;

class Validator extends Base\Validator
{
    protected static $editRules;
    protected static $fetchAllRules;
    protected static $retrieveBanksSuccessRules;
    protected static $retrieveBanksRules;

    public function rules()
    {
        $rules = [
            Entity::IFSC             => 'string',
            Entity::NAME             => 'string',
            Entity::HANDLE           => 'string',
            Entity::UPI_IIN          => 'string',
            Entity::UPI_FORMAT       => 'string',
            Entity::ACTIVE           => 'boolean',
            Entity::SPOC             => 'array',
            Entity::GATEWAY_DATA     => 'array',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::NAME             => 'required',
            Entity::HANDLE           => 'required',
            Entity::ACTIVE           => 'required',
            Entity::UPI_IIN          => 'required',
            Entity::IFSC             => 'sometimes',
            Entity::UPI_FORMAT       => 'sometimes',
            Entity::SPOC             => 'sometimes',
            Entity::GATEWAY_DATA     => 'sometimes',
        ]);

        return $rules;
    }

    public function makeEditRules()
    {
        $rules = $this->makeRules([
            Entity::UPI_IIN          => 'required',
            Entity::ACTIVE           => 'sometimes',
            Entity::HANDLE           => 'sometimes',
            Entity::NAME             => 'sometimes',
            Entity::IFSC             => 'sometimes',
            Entity::UPI_FORMAT       => 'sometimes',
            Entity::SPOC             => 'sometimes',
            Entity::GATEWAY_DATA     => 'sometimes',
        ]);

        return $rules;
    }

    public function makeFetchAllRules()
    {
        $rules = $this->makeRules([
            Entity::UPI_IIN          => 'required',
            Entity::IFSC             => 'sometimes',
            Entity::NAME             => 'sometimes',
            Entity::UPI_FORMAT       => 'sometimes',
            Entity::ACTIVE           => 'sometimes',
            Entity::SPOC             => 'sometimes',
            Entity::GATEWAY_DATA     => 'sometimes',
        ]);

        return $rules;
    }

    public function makeRetrieveBanksSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::UPI_IIN          => 'required',
            Entity::IFSC             => 'sometimes',
            Entity::NAME             => 'sometimes',
            Entity::UPI_FORMAT       => 'sometimes',
            Entity::ACTIVE           => 'sometimes',
            Entity::SPOC             => 'sometimes',
            Entity::GATEWAY_DATA     => 'sometimes',
        ])->wrapRules(Entity::BANKS, true);

        return $rules;
    }

    public function makeRetrieveBanksRules()
    {
        $rules = $this->makeRules([
            Entity::HANDLE          => 'required',
            Context::REQUEST_ID     => 'sometimes',
        ]);

        return $rules;
    }

}
