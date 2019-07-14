<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Base\BuilderEx;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_transaction';

    protected function addQueryParamResponse(BuilderEx $query, $params)
    {
        if ($params[Entity::RESPONSE] === 'history')
        {
            $query->where(function(BuilderEx $query)
            {
                $query->where(Entity::TYPE, '!=', Type::PAY)
                      ->orWhere(Entity::STATUS, '!=', Status::CREATED);
            });
        }
    }
}
