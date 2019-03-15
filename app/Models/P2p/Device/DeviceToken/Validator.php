<?php

namespace RZP\Models\P2p\Device\DeviceToken;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Upi\ClientLibrary;

class Validator extends Base\Validator
{
    public function rules()
    {
        $rules = [
            Entity::DEVICE_ID        => 'string',
            Entity::HANDLE           => 'string',
            Entity::STATUS           => 'string',
            Entity::GATEWAY_DATA     => 'array',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::GATEWAY_DATA     => 'sometimes',
        ]);

        return $rules;
    }
}
