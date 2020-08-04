<?php


namespace RZP\Models\VirtualAccountTpv;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::VIRTUAL_ACCOUNT_TPV;

    public function isTpvEnabledForVa(string $virtualAccountId)
    {
        $count = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where(Entity::VIRTUAL_ACCOUNT_ID, $virtualAccountId)
                      ->where(Entity::IS_ACTIVE, true)
                      ->count();

        return ($count > 0);
    }
}
