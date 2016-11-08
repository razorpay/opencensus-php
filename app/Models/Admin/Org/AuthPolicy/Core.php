<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $policy = (new Entity)->build($input);

        $this->repo->saveOrFail($policy);

        return $policy;
    }
}
