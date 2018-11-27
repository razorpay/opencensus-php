<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $addRules;
    protected static $validateRules;
    protected static $fetchAllRules;

    protected function rules()
    {
        $rules = [
            Entity::DEVICE_ID    => 'string',
            Entity::ENTITY_TYPE  => 'string',
            Entity::ENTITY_ID    => 'string',
            Entity::NAME         => 'string',
        ];

        return $rules;
    }

    protected function getCreateRules()
    {
        $rules = $this->makeRules([
            Entity::DEVICE_ID    => 'sometimes',
            Entity::ENTITY_TYPE  => 'sometimes',
            Entity::ENTITY_ID    => 'sometimes',
            Entity::NAME         => 'sometimes',
        ]);

        return $rules;
    }

    protected function getAddRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getValidateRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
