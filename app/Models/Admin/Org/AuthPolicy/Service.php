<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input)
    {
        $policy = $this->core->create($input);

        return $policy->toArrayPublic();
    }

    public function validate($admin, $password)
    {
        $policy = new Entity;

        $validator = new Validator;

        return $validator->setPolicy($policy)
                         ->validate($admin, $password);
    }

    public function validateLogin($admin, $password)
    {
        $policy = new Entity;

        $validator = new Validator;

        return $validator->setPolicy($policy)
                         ->validate($admin, $password, 'login');
    }
}
