<?php


namespace RZP\Models\Dispute\Evidence;

use RZP\Constants\Table;
use RZP\Models\Base\Repository as BaseRepository;


class Repository extends BaseRepository
{
    public $entity = Table::DISPUTE_EVIDENCE;



    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::ID, 'desc');
    }
}