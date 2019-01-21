<?php

namespace RZP\Models\EntityOrigin;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input, Base\PublicEntity $entity): Entity
    {
        $entityOrigin = new Entity;

        $entityOrigin->build($input);

        $entityOrigin->entity()->associate($entity);

        $this->repo->saveOrFail($entityOrigin);

        return $entityOrigin;
    }
}
