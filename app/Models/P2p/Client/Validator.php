<?php

namespace RZP\Models\P2p\Client;

use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $createRules;

    public function rules()
    {
        $rules = [
            Entity::HANDLE       => 'string',
            Entity::CLIENT_ID    => 'string',
            Entity::CLIENT_TYPE  => 'string',
            Entity::SECRETS      => 'array',
            Entity::GATEWAY_DATA => 'array',
            Entity::CONFIG       => 'array',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        return $this->makeRules([
            Entity::HANDLE       => 'required',
            Entity::CLIENT_ID    => 'required',
            Entity::CLIENT_TYPE  => 'required',
            Entity::SECRETS      => 'required',
            Entity::GATEWAY_DATA => 'required',
            Entity::CONFIG       => 'required',
        ]);
    }
}
