<?php

namespace RZP\Models\Admin\Org\PasswordPolicy\Rules;

use RZP\Exception;

class MaxLengthRule extends Rule
{
    protected $maxLength;

    public function __construct($maxLength)
    {
        $this->maxLength = $maxLength;
    }

    public function validate(string $password)
    {
        if (strlen($password) > $maxLength)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password should be maximum ' . $minLength . ' characters');
        }
    }
}