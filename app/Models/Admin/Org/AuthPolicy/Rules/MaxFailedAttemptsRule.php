<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class MaxFailedAttemptsRule extends Base
{
    protected $maxFailedAttempts;

    public function __construct($maxFailedAttempts)
    {
        $this->maxFailedAttempts = $maxFailedAttempts;
    }

    public function validate($admin, $password)
    {
        if ($admin->getFailedAttempts() > $this->maxFailedAttempts)
        {
            $admin->disable();
            $admin->saveOrFail();

            throw new Exception\BadRequestValidationFailureException(
                'You have exceeded maxmium number of login attempts.');
        }
    }
}