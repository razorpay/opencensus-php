<?php

namespace RZP\Models\P2p\Transaction\Concern;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $editRules;

    public function rules()
    {
        $rules = [
            Entity::TRANSACTION_ID           => 'string',
            Entity::DEVICE_ID                => 'string',
            Entity::HANDLE                   => 'string',
            Entity::GATEWAY_DATA             => 'array',
            Entity::STATUS                   => 'string',
            Entity::COMMENT                  => 'string|max:255',
            Entity::GATEWAY_REFERENCE_ID     => 'string',
            Entity::RESPONSE_CODE            => 'string',
            Entity::RESPONSE_DESCRIPTION     => 'string',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::COMMENT                  => 'required',
        ]);

        return $rules;
    }

    public function makeEditRules()
    {
        $rules = $this->makeRules([
            Entity::GATEWAY_REFERENCE_ID    => 'sometimes',
            Entity::GATEWAY_DATA            => 'sometimes',
            Entity::INTERNAL_STATUS         => 'sometimes',
            Entity::RESPONSE_CODE           => 'sometimes',
            Entity::RESPONSE_DESCRIPTION    => 'sometimes',
        ]);

        return $rules;
    }
}
