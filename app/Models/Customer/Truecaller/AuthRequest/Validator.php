<?php

namespace RZP\Models\Customer\Truecaller\AuthRequest;

use RZP\Base\Validator as BaseValidator;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends BaseValidator
{
    protected static $createRules = [
        'context' => 'sometimes|string',
        'service' => 'sometimes|string',
    ];

    protected static $verifyTruecallerRequestRules = [
        'request_id'            => 'required|string|max:64',
        'device_token'          => 'sometimes|string|max:14',
        'contact'               => 'sometimes|contact_syntax|phone:AUTO,LENIENT,IN,mobile,fixed_line',
        'email'                 => 'sometimes|email',
        '_'                     => 'sometimes|array',
        'language_code'         => 'sometimes',
        'address_consent'       => 'sometimes',
    ];

    /**
     * @param string $status
     * @throws BadRequestValidationFailureException
     */
    public static function isValidStatus(string $status): void
    {
        if (array_key_exists($status, Constants::VALID_STATUSES) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid status: ' . $status);
        }
    }

    public static function validateVerifyTruecallerRequestInput($input): void
    {
        (new static)->validateInput('verifyTruecallerRequest', $input);
    }
}
