<?php

namespace RZP\Models\SubVirtualAccount;

use RZP\Models\Base;

/**
 * Class Repository
 *
 * @package RZP\Models\SubVirtualAccount
 */
class Repository extends Base\Repository
{
    protected $entity = 'sub_virtual_account';

    /**
     * Get Sub Virtual Account if exists with similar details.
     * @param  array           $input
     * @return Entity|null
     */
    public function getSubVirtualAccountWithSimilarDetails(array $input)
    {
        return $this->newQuery()
                    ->where(Entity::MASTER_ACCOUNT_NUMBER, $input[Entity::MASTER_ACCOUNT_NUMBER])
                    ->where(Entity::SUB_ACCOUNT_NUMBER, $input[Entity::SUB_ACCOUNT_NUMBER])
                    ->first();
    }
}

