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

    public function fetch(array $input)
    {
        (new Validator())->validateInput('fetch', $input);
        $entityOrigin = (new Core)->fetchEntityOriginByEntityIdAndType($input[Entity::ENTITY_TYPE], $input[Entity::ENTITY_ID]);

        return $entityOrigin->attributesToArray();
    }
}
