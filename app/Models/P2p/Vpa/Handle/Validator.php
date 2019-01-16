<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $fetchAllRules;

    public function rules()
    {
        $rules = [
            Entity::CODE         => 'string',
            Entity::MERCHANT_ID  => 'string',
            Entity::ACQUIRER     => 'string',
            Entity::ACTIVE       => 'string',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::CODE         => 'sometimes',
            Entity::MERCHANT_ID  => 'sometimes',
            Entity::ACQUIRER     => 'sometimes',
            Entity::ACTIVE       => 'sometimes',
        ]);

        return $rules;
    }

    public function makeFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
