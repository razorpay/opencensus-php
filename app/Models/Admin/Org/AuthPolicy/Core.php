<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use RZP\Models\Base;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $policy = (new Entity)->build($input);

        $policy->setAuditAction(Action::CREATE_AUTH_POLICY);

        $this->repo->saveOrFail($policy);

        return $policy;
    }
}
