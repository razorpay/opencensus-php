<?php

namespace RZP\Models\P2p\Device\DeviceToken;

use RZP\Exception;
use RZP\Models\P2p\Base;

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
        $rules = $this->makeRules([]);

        $rules->arrayRules(ClientLibrary::CL, [
            ClientLibrary::CAPABILITY   => 'required|string',
            ClientLibrary::CHALLENGE    => 'required|string',
        ]);

        return $rules;
    }

    public function makeClSuccessRules()
    {
        $rules = $this->makeRules([]);

        $rules->arrayRules(ClientLibrary::CL, [
            ClientLibrary::TOKEN        => 'required|string',
            ClientLibrary::PAYLOAD      => 'required|string',
        ]);

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::GATEWAY_DATA     => 'sometimes',
        ]);

        $rules->merge($this->makeClRules()->toArray());

        return $rules;
    }

    public function makeAddRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeRefreshClTokenRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeDeregisterRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
