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

    /**
     * @param string $network
     * @param string $gatewayCode
     * @param string $code
     * @return mixed
     */
    public function getReasonFromAttributes(string $network, string $gatewayCode, string $code)
    {
        $query = $this->newQuery()
                      ->select('*')
                      ->where(Entity::NETWORK, $network)
                      ->where(Entity::GATEWAY_CODE, $gatewayCode);

        if( empty($code) === false ){
            return $query->where(Entity::CODE, $code)->get();
        }

        return $query->get();
    }

    public function getReasonByNetworkAndGatewayCode(string $network, string $gatewayCode)
    {
        return $this->newQuery()
            ->where(Entity::NETWORK, $network)
            ->where(Entity::GATEWAY_CODE, $gatewayCode)
            ->firstOrFail();
    }
}
