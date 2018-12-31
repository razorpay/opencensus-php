<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $addRules;
    protected static $validateRules;
    protected static $fetchAllRules;

    public function rules()
    {
        $rules = [
            Entity::DEVICE_ID    => 'string',
            Entity::ENTITY_TYPE  => 'string',
            Entity::ENTITY_ID    => 'string',
            Entity::NAME         => 'string',
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
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeValidateRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
