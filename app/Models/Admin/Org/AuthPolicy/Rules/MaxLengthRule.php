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

    public function validate($admin, $password)
    {
        if (strlen($password) > $maxLength)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password should be maximum ' . $minLength . ' characters');
        }
    }
}