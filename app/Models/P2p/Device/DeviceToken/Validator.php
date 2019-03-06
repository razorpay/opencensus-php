<?php

namespace RZP\Models\P2p\Device\DeviceToken;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Upi\ClientLibrary;

class Validator extends Base\Validator
{
    protected static $addRules;
    protected static $refreshClTokenRules;
    protected static $deregisterRules;

    public function rules()
    {
        $rules = [
            Entity::DEVICE_ID        => 'string',
            Entity::HANDLE           => 'string',
            Entity::GATEWAY_DATA     => 'array',
            Entity::STATUS           => 'string',
            Entity::SDK_DATA         => 'array',
        ];

        return $rules;
    }

    public function makeSdkDataRules()
    {
        $rules = $this->makeRules();

        $rules->arrayRules(Entity::SDK_DATA, []);

        return $rules;
    }

    public function makeClSuccessRules()
    {
        $rules = $this->makeRules();

        $arrayRules = ClientLibrary::rules()->with([
            ClientLibrary::TOKEN        => 'required',
            ClientLibrary::PAYLOAD      => 'required',
        ]);

        $rules->arrayRules(ClientLibrary::CL, $arrayRules->toArray());

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::GATEWAY_DATA     => 'sometimes',
            Entity::SDK_DATA         => 'sometimes',
        ]);

        return $rules;
    }
}
