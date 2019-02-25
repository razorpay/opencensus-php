<?php

namespace Functional\Fixtures\Entity;

use RZP\Tests\Functional\Fixtures\Entity\Base;

class EntityOrigin extends Base
{
    public function create(array $attributes = [])
    {
        $entityOrigin = parent::create($attributes);

        $entityOrigin->saveOrFail();

        return $entityOrigin;
    }
}
