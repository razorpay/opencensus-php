<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class EnforceInitialPasswordChangeRule extends Base
{
    protected $enforceInitialPasswordChange;

    public function __construct($enforceInitialPasswordChange)
    {
        $this->enforceInitialPasswordChange = $enforceInitialPasswordChange;
    }

    public function validate($admin)
    {
        if (($admin->isFirstLogin() === true) and
            ($this->enforceInitialPasswordChange === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please change the password');
        }
    }
}