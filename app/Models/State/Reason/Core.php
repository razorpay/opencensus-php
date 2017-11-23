<?php

namespace RZP\Models\State\Reason;

use RZP\Models\Base;
use RZP\Models\State\Entity as StateEntity;

class Core extends Base\Core
{
    public function create(array $input, StateEntity $state): Entity
    {
        $reason = (new Entity)->build($input);

        $reason->state()->associate($state);

        $this->repo->saveOrFail($reason);

        return $reason;
    }
}
