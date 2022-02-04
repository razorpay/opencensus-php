<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Adjustment\Entity;
use RZP\Models\Adjustment\Status;

class Adjustment extends Base
{
    public function create(array $attributes = [])
    {
        $defaultValues = [
            Entity::STATUS      => Status::PROCESSED,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $adjustment = parent::create($attributes);

        return $adjustment;
    }
}
