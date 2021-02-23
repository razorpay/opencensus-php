<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class UpperLowerCaseRule extends Base
{
    public function validate($admin, array $data)
    {
        $password = $data['password'] ?? '';

        $uppercase = $this->isUpperCaseInPassword($password);

        $lowercase = $this->isLowerCaseInPassword($password);

        if ($lowercase === false || $uppercase === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password must have combination of uppercase and lowercase characters');
        }
    }

    protected function isUpperCaseInPassword($password)
    {
        if(strtolower($password) !== $password)
        {
            return true;
        }

        return false;
    }

    protected function isLowerCaseInPassword($password)
    {
        if(strtoupper($password) !== $password)
        {
            return true;
        }

        return false;
    }
}
