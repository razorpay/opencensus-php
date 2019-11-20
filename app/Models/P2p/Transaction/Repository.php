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
            $query->whereNotIn(Entity::STATUS, [Status::CREATED])
                  ->where(function ($query) {
                        $query->whereNotIn(Entity::TYPE, [Type::PAY])
                            ->orWhereNotIn(Entity::FLOW, [Flow::CREDIT])
                            ->orWhereNotIn(Entity::STATUS, [Status::PENDING, Status::FAILED]);
                  });
        }
        elseif ($params[Entity::RESPONSE] === 'pending')
        {
            $timestamp = $query->getModel()->freshTimestamp();

            $query->where(Entity::STATUS, '=', Status::REQUESTED)
                  ->where(Entity::EXPIRE_AT, '>', $timestamp);
        }
    }
}
