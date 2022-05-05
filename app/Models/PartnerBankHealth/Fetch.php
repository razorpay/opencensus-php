<?php

namespace RZP\Models\PartnerBankHealth;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Constants::SOURCE             => 'sometimes|filled|string|custom',
            Constants::INTEGRATION_TYPE   => 'sometimes|filled|string|custom',
            Constants::PAYOUT_MODE        => 'sometimes|filled|string|custom',
            Constants::EVENT_TYPE_PATTERN => 'sometimes|string|custom',
        ]
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Constants::SOURCE,
            Constants::INTEGRATION_TYPE,
            Constants::PAYOUT_MODE,
            Constants::EVENT_TYPE_PATTERN,
        ],
    ];

    protected function validateSource($attribute, $value)
    {
        Validator::validateSource($attribute, $value);
    }

    protected function validateIntegrationType($attribute, $value)
    {
        Validator::validateIntegrationType($attribute, $value);
    }

    protected function validatePayoutMode($attribute, $value)
    {
        Validator::validateMode($attribute, $value);
    }

    protected function validateEventTypePattern($attribute, $value)
    {
        try
        {
            list($source, $integrationType, $mode) = explode('.', $value);
        }
        catch(\Throwable $exception)
        {
            throw new BadRequestValidationFailureException("Invalid $attribute : $value");
        }

        if (empty($source) === false and $source !== '%')
        {
            Validator::validateSource(Constants::SOURCE, $source);
        }

        if (empty($integrationType) === false and $integrationType !== '%')
        {
            Validator::validateIntegrationType(Constants::INTEGRATION_TYPE, $integrationType);
        }

        if (empty($mode) === false and $mode !== '%')
        {
            Validator::validateMode(Constants::MODE, $mode);
        }
    }
}
