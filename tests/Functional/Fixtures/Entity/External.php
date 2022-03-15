<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\External\Entity;

class External extends Base
{
    public function create(array $attributes = [])
    {
        $external = parent::create($attributes);
        return $external;
    }
}
