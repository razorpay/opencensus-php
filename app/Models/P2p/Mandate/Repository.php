<?php

namespace RZP\Models\P2p\Mandate;

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
}
