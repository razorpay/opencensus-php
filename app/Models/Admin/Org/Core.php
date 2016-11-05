<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $org = (new Entity)->build($input);

        $this->repo->saveOrFail($org);

        return $org;
    }
}
