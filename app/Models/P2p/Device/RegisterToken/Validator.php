<?php

namespace RZP\Models\P2p\Device\RegisterToken;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $addRules;
    protected static $verifyRules;

    public function rules()
    {
        $rules = [
            Entity::TOKEN        => 'string',
            Entity::MERCHANT_ID  => 'string',
            Entity::DEVICE_ID    => 'string',
            Entity::HANDLE       => 'string',
            Entity::STATUS       => 'string',
            Entity::DEVICE_DATA  => 'array|custom',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::DEVICE_DATA  => 'sometimes',
        ]);

        return $rules;
    }

    public function makeVerificationSuccessRules()
    {
        return $this->makeRules([
            Entity::TOKEN         => 'required',
            Entity::DEVICE_DATA   => 'sometimes',
        ]);
    }

    public function makeVerificationRules()
    {
        return $this->makeRules([
            Entity::TOKEN       => 'required',
        ]);
    }

    public function validateDeviceData()
    {
        // TODO:: implement device
    }
}
