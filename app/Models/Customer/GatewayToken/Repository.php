<?php

namespace RZP\Models\Customer\GatewayToken;

use RZP\Models\Base;
use RZP\Models\Customer\Token;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_token';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
        Entity::TERMINAL_ID => 'sometimes|alpha_num',
        Entity::TOKEN_ID    => 'sometimes|alpha_num',
        Entity::REFERENCE   => 'sometimes|alpha_num',
    ];

    public function findByTokenAndReference(
        Token\Entity $token,
        $reference,
        $relations = [])
    {
        return $this->newQuery()
                    ->where(Entity::TOKEN_ID, '=', $token->getId())
                    ->where(Entity::REFERENCE, '=', $reference)
                    ->where(function ($q) use ($reference)
                    {
                        //
                        // For Zoho (local charge-at-will), there
                        // won't be any reference. At least initially.
                        // Later, when we have references for all the older
                        // tokens and they start sending for all the newer
                        // ones, we'll remove the null check here.
                        //
                        $q->where(Entity::REFERENCE, '=', $reference)
                          ->orWhereNull(Entity::REFERENCE);
                    })
                    ->with($relations)
                    ->get();
    }
}
