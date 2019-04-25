<?php

namespace RZP\Models\EntityOrigin;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $entityOrigin = (new Core)->createFromInternalApp($input);

        return $entityOrigin->toArrayPublic();
    }
}
