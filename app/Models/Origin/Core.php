<?php

namespace RZP\Models\Origin;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input, Base\PublicEntity $entity): Entity
    {
        $origin = new Entity;

        $origin->generateId();

        $origin->build($input);

        $origin->entity()->associate($entity);

        $origin->saveOrFail();

        return $origin;
    }
}
