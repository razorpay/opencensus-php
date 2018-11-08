<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use Hash;
use RZP\Exception;

class MaxPasswordRetainRule extends Base
{
    protected $maxPasswordRetain;

    public function __construct($maxPasswordRetain)
    {
        $this->maxPasswordRetain = $maxPasswordRetain;
    }

    public function validate($admin, array $data)
    {
        $password = $data['password'] ?? '';

        $previousPasswords = $admin->getOldPasswords();

        foreach ($previousPasswords as $oldPassword)
        {
            if (Hash::check($password, $oldPassword) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Password cannot be same as last ' . $this->maxPasswordRetain . ' passwords.');
            }
        }
    }
}
