<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;
use Carbon\Carbon;

class PasswordExpiryRule extends Base
{
    protected $expiresIn;

    public function __construct($passwordExpiry)
    {
        $this->passwordExpiry = $passwordExpiry;
    }

    public function validate($admin, array $data)
    {
        $time = Carbon::now()->subDays($this->passwordExpiry)->getTimestamp();

        $passwordChangedAt = $admin->getPasswordChangedAt();

        if ($passwordChangedAt !== null)
        {
            if ($time > $admin->getPasswordChangedAt())
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Account password has expired. Please contact administrator.');
            }
        }
    }
}
