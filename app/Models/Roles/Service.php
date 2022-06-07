<?php

namespace RZP\Models\Roles;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->validator = new Validator;
    }

    public function listRolesForMerchant($input)
    {
        $this->validator->validateInput('view', $input);

        $roles = $this->core->listRolesForMerchant($input);

        return $roles;
    }
}
