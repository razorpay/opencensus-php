<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class MaxPasswordRetainRule extends Base
{
    protected $maxPasswordRetain;

    public function __construct($maxPasswordRetain)
    {
        $this->maxPasswordRetain = $maxPasswordRetain;
    }

    public function validate($admin, $password)
    {
        $previousPasswords = $admin->getOldPasswords();

        if (in_array($admin->getPassword(), $previousPasswords, true) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password cannot be same as last ' . $this->maxPasswordRetain . ' passwords');
        }
    }
}