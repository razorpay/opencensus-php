<?php


namespace RZP\Models\Reward;

use RZP\Models\Base;


class Repository extends Base\Repository
{
    protected $entity = 'reward';

    public function update(string $id, array $params)
    {
        $this->newQuery()
            ->where(Entity::ID, '=', $id)
            ->update($params);
    }

    public function fetchUpdatedRewardColumnsById(string $rewardId, array $params)
    {
        $query = $this->newQuery()
            ->select($params)
            ->where(Entity::ID, '=', $rewardId);
        return $query->first();
    }

}
