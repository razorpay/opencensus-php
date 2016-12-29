<?php

namespace RZP\Models\Device;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::IMEI         => 'required',
        Entity::OS_VERSION   => 'required',
        Entity::PACKAGE_NAME => 'required',
        Entity::CHALLENGE    => 'required',

    ];

    protected static $verifyRules = [
        'message' => 'required',
        'number'  => 'required',
        'keyword' => 'required'
    ];

    protected static $verifyValidator = [
        'keyword'
    ];

    protected function validateVerify($input)
    {
        $keyword = trim(strtolower($input['keyword']));

        if ($keyword !== 'verify')
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid keyword value given', 'keyword', $keyword);
        }
    }
}
