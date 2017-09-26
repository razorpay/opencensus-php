<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class MaxFailedAttemptsRule extends Base
{
    protected $maxFailedAttempts;

    public function __construct($maxFailedAttempts)
    {
        parent::__construct();

        $this->maxFailedAttempts = $maxFailedAttempts;
    }

    public function validate($admin, array $data)
    {
        if ($admin->isLocked() === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Your account has been locked');
        }

        if ($admin->getFailedAttempts() >= $this->maxFailedAttempts)
        {
            $admin->lock();

            $this->repo->saveOrFail($admin);

            throw new Exception\BadRequestValidationFailureException(
                'You have exceeded maxmium number of login attempts. Your account has been locked.');
        }
    }
}
