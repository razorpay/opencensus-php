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

    public function validate($admin, $password)
    {
        if (($admin->isInitialLogin() === true) and
            ($this->enforceInitialPasswordChange === true))
        {
            return [
                'action' => 'password_reset'
            ];
        }
    }
}