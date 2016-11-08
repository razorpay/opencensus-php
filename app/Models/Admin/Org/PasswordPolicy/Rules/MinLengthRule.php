<?php

namespace RZP\Models\Admin\Org\PasswordPolicy\Rules;

use RZP\Exception;

class MinLengthRule extends Rule
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
            throw new Exception\BadRequestException(
                );
        }
    }
}