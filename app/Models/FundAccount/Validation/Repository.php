<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\FundAccount\Type;

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
            ->where(Entity::FUND_ACCOUNT_TYPE, "=", Type::BANK_ACCOUNT)
            ->take($count)
            ->orderBy(Entity::RETRY_AT, 'asc')
            ->get()->pluck('id')->all();
    }
}
