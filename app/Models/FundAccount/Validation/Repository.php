<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::FUND_ACCOUNT_VALIDATION;

    protected $expands = [
        Entity::FUND_ACCOUNT,
    ];

    public function getFundAccountValidationsToRetry($time, $count)
    {
        return $this->newQuery()
            ->select(Entity::ID)
            ->where(Entity::RETRY_AT, '<', $time)
            ->where(Entity::STATUS, "=" , Status::CREATED)
            ->take($count)
            ->orderBy(Entity::RETRY_AT, 'asc')
            ->get()->pluck('id')->all();
    }

    public function getFundAccountValidationById(string $id) : Entity
    {
        return $this->newQuery()
            ->where(Entity::ID, $id)
            ->first();
    }
}
