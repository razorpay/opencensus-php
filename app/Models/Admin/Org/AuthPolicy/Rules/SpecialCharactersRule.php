<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class SpecialCharactersRule extends Base
{
    public function validate($admin, array $data)
    {
        $password = $data['password'] ?? '';

        // anything which is not upper or lower case letter and not a digit
        // is considered as special character
        if (preg_match('/[^a-zA-Z\d]/', $password) === 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password must have special characters');
        }
    }
}
