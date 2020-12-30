<?php

namespace RZP\Models\Application\ApplicationTags;

use RZP\Models\Base;

class Core extends Base\Core
{

    public function create(array $input): Entity
    {
        $appMappingEntity = (new Entity)->build($input);

        $this->repo->saveOrFail($appMappingEntity);

        return $appMappingEntity;
    }

    public function delete(Entity $appMapping)
    {
        $this->repo->deleteOrFail($appMapping);
    }
}
