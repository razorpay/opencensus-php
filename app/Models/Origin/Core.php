<?php

namespace RZP\Models\Origin;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input, Base\PublicEntity $entity): Entity
    {
        $origin = new Entity;

        $origin->build($input);

        $origin->entity()->associate($entity);

        $this->repo->saveOrFail($origin);

        return $origin;
    }
}
