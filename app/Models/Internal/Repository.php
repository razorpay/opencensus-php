<?php

namespace RZP\Models\Internal;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::INTERNAL;

    public function fetchByUTR($utr)
    {
        return $this->newQuery()
                    ->where(Entity::UTR, $utr)
                    ->first();
    }
}
