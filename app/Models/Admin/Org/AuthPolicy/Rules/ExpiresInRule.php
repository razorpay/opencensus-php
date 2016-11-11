<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class ExpiresInRule extends Base
{
    protected $expiresIn;

    public function __construct($expiresIn)
    {
        $this->expiresIn = $expiresIn;
    }

    public function validate($admin, $password)
    {
        if ($admin->getPasswordExpiry() < time())
        {
            $adminArray = $admin->toArray();
            $adminArray['action'] = 'password_reset';

            return $adminArray;
        }
    }
}