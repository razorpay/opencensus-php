<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $fetchAllRules;

    protected function rules()
    {
        $rules = [
            Entity::HANDLE       => 'string',
            Entity::MERCHANT_ID  => 'string',
            Entity::ACQUIRER     => 'string',
            Entity::ACTIVE       => 'string',
        ];

        return $rules;
    }

    protected function getCreateRules()
    {
        $rules = $this->makeRules([
            Entity::HANDLE       => 'sometimes',
            Entity::MERCHANT_ID  => 'sometimes',
            Entity::ACQUIRER     => 'sometimes',
            Entity::ACTIVE       => 'sometimes',
        ]);

        return $rules;
    }

    protected function getFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
