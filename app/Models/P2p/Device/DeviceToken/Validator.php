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
            Entity::CL               => 'array',
        ];

        return $rules;
    }

    public function makeClRules()
    {
        $rules = $this->makeRules();

        $arrayRules = ClientLibrary::rules()->with([
            ClientLibrary::CAPABILITY   => 'required',
            ClientLibrary::CHALLENGE    => 'required',
        ]);

        $rules->arrayRules(ClientLibrary::CL, $arrayRules->toArray());

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
        ]);

        $rules->merge($this->makeClRules());

        return $rules;
    }
}
