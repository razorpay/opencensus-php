<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class LockedAccountRule extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function validate($admin, array $data)
    {
        if ($admin->isLocked() === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Your account has been locked');
        }
    }
}
