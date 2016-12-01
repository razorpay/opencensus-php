<?php

namespace RZP\Models\P2p;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p';

    public function fetchWithSourceSink($id, $columns = ['*'])
    {
        return $this->newQuery()
                    ->select($columns)
                    ->with(['source', 'sink'])
                    ->find($id);
    }
}
