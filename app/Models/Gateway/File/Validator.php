<?php

namespace RZP\Models\Gateway\File;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    const TIME_RANGE = 'time_range';

    protected static $createRules = [
        Entity::TYPE       => 'required|string|custom',
        Entity::GATEWAY    => 'required|string',
        Entity::BANK       => 'required|string',
        Entity::SENDER     => 'filled|email',
        Entity::RECIPIENTS => 'filled|array|custom',
        Entity::FROM       => 'required|epoch',
        Entity::TO         => 'required|epoch',
        Entity::SCHEDULED  => 'filled|boolean'
    ];

    protected static $acknowledgeRules = [
        Entity::PARTIALLY_PROCESSED => 'filled|in:1',
    ];

    protected static $createValidators = [
        Entity::GATEWAY,
        Entity::BANK,
        self::TIME_RANGE,
    ];

    protected function validateType(string $attribute, string $type)
    {
        if (Type::isValidType($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "$type is not a valid gateway file type");
        }
    }

    protected function validateGateway(array $input)
    {
        $type = $input[Entity::TYPE];

        $gateway = $input[Entity::GATEWAY];

        $supportedGatewaysForType = array_keys(Constants::GATEWAY_SUPPORTED_BANKS[$type]);

        if (in_array($gateway, $supportedGatewaysForType, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "$gateway  s not a supported gateway for type");
        }
    }

    protected function validateBank(array $input)
    {
        $type = $input[Entity::TYPE];

        $bank = $input[Entity::BANK];

        $gateway = $input[Entity::GATEWAY];

        $supportedBanks = Constants::GATEWAY_SUPPORTED_BANKS[$type][$gateway];

        if (in_array($bank, $supportedBanks, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "$bank is not supported for $type and $gateway");
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
