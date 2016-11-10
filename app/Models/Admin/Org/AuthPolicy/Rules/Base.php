<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

abstract class Base
{
    public function validate($admin, $password)
    {
        throw new Exception\RuntimeException(
            'Validate function not implemented');
    }
}