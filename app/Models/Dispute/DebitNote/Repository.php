<?php

namespace RZP\Models\Dispute\DebitNote;

use RZP\Constants\Table;
use RZP\Models\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    public $entity = Table::DEBIT_NOTE;

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::ID, 'desc');
    }
}