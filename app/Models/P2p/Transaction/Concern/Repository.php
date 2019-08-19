<?php

namespace RZP\Models\P2p\Transaction\Concern;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_concern';

    public function findAll(array $input)
    {
        $query =  $this->newQuery()->where($input);

        return $query->get();
    }

    protected function buildFetchQueryAdditional($params, $query)
    {
        $query->whereNotIn(Entity::STATUS, [Status::CREATED]);
    }
}
