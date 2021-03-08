<?php

namespace RZP\Models\CardMandate;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'card_mandate';

    public function findByMandateIdOrFail($id)
    {
        return $this->newQuery()
                    ->where(Entity::MANDATE_ID, '=', $id)
                    ->firstOrFailPublic();
    }
}
