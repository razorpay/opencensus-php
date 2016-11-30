<?php

namespace RZP\Models\P2p;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;

class Core extends Base\Core
{
    public function create($input)
    {
        $p2p = (new Entity)->build($input);

        $p2p->generateId();

        $this->repo->saveOrFail($p2p);

        return $p2p;
    }
}
