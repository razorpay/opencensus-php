<?php

namespace RZP\Models\Admin\Org\PasswordPolicy;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function __construct(Core $core)
    {
        parent::__construct();

        $this->core = $core;
    }

    public function create(array $input)
    {
        $policy = $this->core->create($input);

        return $policy->toArrayPublic();
    }

    public function validate($organisationId, $password)
    {
        $policy = new Entity;

        $validator = new Validator;

        $validator->setPolicy($policy)
                ->validatePassword($password);
    }
}
