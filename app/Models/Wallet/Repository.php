<?php

namespace RZP\Models\Wallet;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'wallets';

    public function findByCustomerId(string $id)
    {
        $id = Entity::verifyIdAndSilentlyStripSign($id);

        return $this->newQuery()
                    ->where(Entity::CUSTOMER_ID, $id)
                    ->first();
    }

}
