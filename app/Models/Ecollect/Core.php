<?php

namespace RZP\Models\Ecollect;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($input)
    {
        $ecollect = (new Entity)->build($input);

        $ecollect->generateId();

        return $ecollect;
    }
}
