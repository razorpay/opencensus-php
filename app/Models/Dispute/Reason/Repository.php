<?php

namespace RZP\Models\Dispute\Reason;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'dispute_reason';

    // These are merchant allowed params to search on. These also act as default params.
    protected $appFetchParamRules = [
        Entity::NETWORK             => 'sometimes|string|custom',
        Entity::GATEWAY_CODE        => 'sometimes|string',
        Entity::CODE                => 'sometimes|string',
        Entity::DESCRIPTION         => 'sometimes|string',
        Entity::GATEWAY_DESCRIPTION => 'sometimes|string',
    ];

    protected function validateNetwork($attribute, $value)
    {
        (new Validator)->validateNetwork($attribute, $value);
    }

    // Get reason ID from network, gateway_code, code
    public function getReasonIdFromAttributes(string $network, string $gatewayCode, string $code) : array
    {
        return $this->newQuery()
                    ->select(Entity::ID)
                    ->where(Entity::NETWORK, $network)
                    ->where(Entity::GATEWAY_CODE, $gatewayCode)
                    ->where(Entity::CODE, $code)
                    ->pluck(Entity::ID)->toArray();
    }
}
