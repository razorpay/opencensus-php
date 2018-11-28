<?php

namespace RZP\Models\P2p\BankAccount\Bank;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $fetchAllRules;

    protected function rules()
    {
        $rules = [
            Entity::IFSC             => 'string',
            Entity::NAME             => 'string',
            Entity::UPI_IIN          => 'string',
            Entity::UPI_FORMAT       => 'string',
            Entity::ACTIVE           => 'string',
            Entity::SPOC             => 'array',
        ];

        return $rules;
    }

    protected function getCreateRules()
    {
        $rules = $this->makeRules([
            Entity::IFSC             => 'sometimes',
            Entity::NAME             => 'sometimes',
            Entity::UPI_IIN          => 'sometimes',
            Entity::UPI_FORMAT       => 'sometimes',
            Entity::ACTIVE           => 'sometimes',
            Entity::SPOC             => 'sometimes',
        ]);

        return $rules;
    }

    protected function getFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
