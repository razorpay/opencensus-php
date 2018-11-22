<?php

namespace RZP\Models\P2p\Device\RegisterToken;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $addRules;
    protected static $verifyRules;

    protected function rules()
    {
        $rules = [
            Entity::TOKEN        => 'string',
            Entity::MERCHANT_ID  => 'string',
            Entity::DEVICE_ID    => 'string',
            Entity::HANDLE       => 'string',
            Entity::STATUS       => 'string',
            Entity::DEVICE_DATA  => 'array',
        ];

        return $rules;
    }

    protected function getCreateRules()
    {
        $rules = $this->makeRules([
            Entity::TOKEN        => 'sometimes',
            Entity::MERCHANT_ID  => 'sometimes',
            Entity::DEVICE_ID    => 'sometimes',
            Entity::HANDLE       => 'sometimes',
            Entity::STATUS       => 'sometimes',
            Entity::DEVICE_DATA  => 'sometimes',
        ]);

        return $rules;
    }

    protected function getAddRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getVerifyRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
