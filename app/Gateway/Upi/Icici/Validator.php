<?php

namespace RZP\Gateway\Upi\Icici;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    public static $vpaRules = [
        'vpa' => 'required|string|max:255',
    ];

    public static $vpaValidators = [
        'vpa'
    ];

    public function validateVpa($input)
    {
        if (preg_match('/[^a-z@\.\-0-9]/i', $input['vpa']))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Vpa Provided is not valid'
            );
        }
    }
}
