<?php

namespace RZP\Models\Gateway\File;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    const TIME_RANGE = 'time_range';

    protected static $createRules = [
        Entity::GATEWAY    => 'required|string|custom',
        Entity::TYPE       => 'required|string',
        Entity::SENDER     => 'sometimes|email',
        Entity::RECIPIENTS => 'required|array|custom',
        Entity::FROM       => 'required|epoch',
        Entity::TO         => 'required|epoch',
        Entity::SCHEDULED  => 'filled|boolean'
    ];

    protected static $createValidators = [
        Entity::TYPE,
        self::TIME_RANGE,
    ];

    protected function validateGateway(string $attribute, string $gateway)
    {
        if (Gateway::isValidGateway($gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $gateway . ' is not a valid gateway');
        }
    }

    protected function validateType(array $input)
    {
        $type = $input[Entity::TYPE];

        if (Type::isValidType($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "$type is not a valid gateway file type");
        }

        $gateway = $input[Entity::GATEWAY];

        $this->validateGatewaySupportsFileType($gateway, $type);
    }

    protected function validateGatewaySupportsFileType(string $gateway, string $type)
    {
        if (in_array($gateway, Constants::SUPPORTED_GATEWAYS[$type], true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "$type not supported for $gateway");
        }
    }

    protected function validateRecipients(string $attribute, array $recipients)
    {
        if (is_associative_array($recipients) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'data provided is not a valid array');
        }

        foreach ($recipients as $email)
        {
            if ($this->isValidEmail($email) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "email id provided is not valid: $email");
            }
        }
    }

    protected function validateTimeRange(array $input)
    {
        $from = $input[Entity::FROM];
        $to = $input[Entity::TO];

        if ($from >= $to)
        {
            throw new Exception\BadRequestValidationFailureException(
                'from cannot be after to');

        }
    }

    protected function isValidEmail(string $email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
