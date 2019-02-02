<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::FUND_ACCOUNT_VALIDATION;

    public function getFundAccountValidationById(string $id)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->first();
    }
}
