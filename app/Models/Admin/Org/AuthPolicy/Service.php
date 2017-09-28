<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use RZP\Models\Base;
use RZP\Models\Admin\Admin;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();
    }

    public function validate(Admin\Entity $admin, array $data, string $op)
    {
        $policy = new Entity;

        $validator = new Validator($policy);

        return $validator->validate($admin, $data, $op);
    }

    public function __call(string $name, array $arguments)
    {
        if (strpos($name, 'validate') === 0)
        {
            $op = lcfirst(substr($name, 8));

            // $data
            if (isset($arguments[1]) === false)
            {
                $arguments[1] = [];
            }

            // $op
            $arguments[] = $op;

            call_user_func_array([$this, 'validate'], $arguments);
        }
    }
}
