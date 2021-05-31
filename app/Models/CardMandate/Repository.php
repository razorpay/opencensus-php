<?php

namespace RZP\Models\CardMandate;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'card_mandate';

    public function findByMandateId($id)
    {
        return $this->newQuery()
                    ->where(Entity::MANDATE_ID, '=', $id)
                    ->first();
    }
}
