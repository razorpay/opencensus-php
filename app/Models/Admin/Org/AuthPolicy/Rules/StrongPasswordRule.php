<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

use ZxcvbnPhp\Zxcvbn;

class StrongPasswordRule extends Base
{
    public function validate($admin, array $data)
    {
        $password = $data['password'] ?? '';

        $zxcvbn = new Zxcvbn();

        $strength = $zxcvbn->passwordStrength($password);

        if ($strength['score'] <= 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password is too weak. Please choose a new password.');
        }
    }
}
