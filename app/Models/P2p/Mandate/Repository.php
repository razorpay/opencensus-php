<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_mandate';

    public function findByUMN(string $umn): Entity
    {
        $mandate = $this->newQuery()
                        ->where(Entity::UMN, '=', $umn)
                        ->firstOrFail();

        return $mandate;
    }

    protected function addQueryParamResponse(BuilderEx $query, $params)
    {
        if ($params[Entity::RESPONSE] === 'active')
        {
            $query->whereIn(Entity::STATUS, [Status::APPROVED, Status::PAUSED]);
        }
        elseif ($params[Entity::RESPONSE] === 'pending')
        {
            $timestamp = $query->getModel()->freshTimestamp();

            $query->where(Entity::STATUS, '=', Status::REQUESTED)
                  ->where(Entity::EXPIRE_AT, '>', $timestamp);
        }
        elseif ($params[Entity::RESPONSE] === 'history')
        {
            $query->whereIn(Entity::STATUS, [Status::REVOKED, Status::REJECTED, Status::COMPLETED, Status::FAILED]);
        }
    }
}
