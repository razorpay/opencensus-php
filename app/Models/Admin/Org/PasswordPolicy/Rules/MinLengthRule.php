<?php

namespace RZP\Models\Admin\Org\PasswordPolicy\Rules;

use RZP\Exception;

class MinLengthRule extends Base
{
    protected $minLength;

    public function __construct($minLength)
    {
        $this->minLength = $minLength;
    }

    public function validate(string $password)
    {
        if (strlen($password) < $minLength)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password should be atleast ' . $minLength . ' characters long');
        }
    }
}