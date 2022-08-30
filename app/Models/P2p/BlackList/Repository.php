<?php

namespace RZP\Models\P2p\BlackList;

use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_blacklist';

    public function findAll(array $input , bool $withThrashed)
    {
        $query = null;

        if($withThrashed)
        {
            $query = $this->newQuery()->withTrashed()->where($input);
        }
        else
        {
            $query = $this->newQuery()->where($input);
        }

        return $query->get()->first();
    }
}
