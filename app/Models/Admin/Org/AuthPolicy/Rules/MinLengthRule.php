<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class MinLengthRule extends Base
{
    protected $minLength;

    public function __construct($minLength)
    {
        $this->minLength = $minLength;
    }

    public function validate($admin, $password)
    {
        if (strlen($password) < $minLength)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password should be atleast ' . $minLength . ' characters long');
        }
    }
}