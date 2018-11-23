<?php

namespace RZP\Models\P2p\Device\DeviceToken;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $addRules;
    protected static $refreshClTokenRules;
    protected static $deregisterRules;

    protected function rules()
    {
        $rules = [
            Entity::DEVICE_ID        => 'string',
            Entity::HANDLE           => 'string',
            Entity::GATEWAY_DATA     => 'array',
            Entity::STATUS           => 'string',
            Entity::CL_CAPABILITY    => 'string',
            Entity::CL_TOKEN         => 'string',
            Entity::CL_PAYLOAD       => 'string',
        ];

        return $rules;
    }

    protected function getCreateRules()
    {
        $rules = $this->makeRules([
            Entity::DEVICE_ID        => 'sometimes',
            Entity::HANDLE           => 'sometimes',
            Entity::GATEWAY_DATA     => 'sometimes',
            Entity::STATUS           => 'sometimes',
            Entity::CL_CAPABILITY    => 'sometimes',
            Entity::CL_TOKEN         => 'sometimes',
            Entity::CL_PAYLOAD       => 'sometimes',
        ]);

        return $rules;
    }

    protected function getAddRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getRefreshClTokenRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getDeregisterRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
