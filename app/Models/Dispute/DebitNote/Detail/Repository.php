<?php

namespace RZP\Models\Dispute\DebitNote\Detail;

use RZP\Constants\Table;
use RZP\Models\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    public $entity = Table::DEBIT_NOTE_DETAIL;

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::ID, 'desc');
    }
}