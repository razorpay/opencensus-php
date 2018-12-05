<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class MaxLengthRule extends Base
{
    protected $maxLength;

    public function __construct($maxLength)
    {
        $this->maxLength = $maxLength;
    }

    public function validate($admin, array $data)
    {
        $password = $data['password'] ?? '';

        if (strlen($password) > $this->maxLength)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password should be maximum ' . $this->maxLength . ' characters');
        }
    }
}
