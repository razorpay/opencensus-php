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

    public function validate($admin, array $data)
    {
        $password = $data['password'] ?? '';

        if (strlen($password) < $this->minLength)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password should be atleast ' . $this->minLength . ' characters long');
        }
    }
}
