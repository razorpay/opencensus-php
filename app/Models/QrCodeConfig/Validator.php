<?php

namespace RZP\Models\QrCodeConfig;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::KEY          => 'required|string|in:cut_off_time',
        Entity::VALUE        => 'sometimes',
    ];

    protected static $editRules = [
        Entity::KEY          => 'required|string|in:cut_off_time',
        Entity::VALUE        => 'sometimes',
    ];

    protected static $createValidators = [
        Entity::VALUE,
    ];

    public function validateCutOffTime($value)
    {
        if (empty($value) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_CONFIG_INVALID_CUT_OFF_TIME_EMPTY);
        }

        if(is_numeric($value) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_CONFIG_INVALID_CUT_OFF_TIME_ALPHA_NUMERIC);
        }

        if ($value < 1)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_CONFIG_INVALID_CUT_OFF_TIME_NON_POSITIVE_CUTOFF);
        }

        if ($value > 86400)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_CONFIG_INVALID_CUT_OFF_TIME_TOO_HIGH);
        }
    }

    public function validateConfigValue($input)
    {
        switch ($input[Entity::KEY])
        {
            case Keys::CUT_OFF_TIME:
                $this->validateCutOffTime($input[Entity::VALUE]);
                break;
        }
    }
}
