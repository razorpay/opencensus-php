<?php

namespace RZP\Models\P2p\Client;

use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $createRules;
    protected static $editRules;

    protected static $createValidators = [
       Entity::SECRETS,
       Entity::CONFIG,
       Entity::GATEWAY_DATA,
    ];

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
            Entity::SECRETS      => 'present',
            Entity::GATEWAY_DATA => 'present',
            Entity::CONFIG       => 'present',
        ]);
    }

    public function makeEditRules()
    {
        return $this->makeRules([
           Entity::HANDLE        => 'required',
           Entity::CLIENT_ID     => 'required',
           Entity::CLIENT_TYPE   => 'required',
           Entity::SECRETS       => 'sometimes',
           Entity::GATEWAY_DATA  => 'sometimes',
           Entity::CONFIG        => 'sometimes',
        ]);
    }

    public function validateSecrets($input)
    {
        $secrets = $input[Entity::SECRETS];

        (new Secrets())->validate($secrets);
    }

    public function validateConfig($input)
    {
        $config = $input[Entity::CONFIG];

        (new Config())->validate($config);
    }

    public function validateGatewayData($input)
    {
        $gatewayData = $input[Entity::GATEWAY_DATA];

        (new GatewayData())->validate($input);
    }
}
