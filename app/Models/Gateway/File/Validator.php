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
        Entity::TYPE              => 'required|string|custom',
        Entity::SOURCE            => 'required|string',
        Entity::SENDER            => 'filled|email',
        Entity::RECIPIENTS        => 'filled|array',
        Entity::RECIPIENTS . '.*' => 'email',
        Entity::FROM              => 'required|epoch',
        Entity::TO                => 'required|epoch',
        Entity::SCHEDULED         => 'filled|boolean',
    ];

    protected static $acknowledgeRules = [
        Entity::PARTIALLY_PROCESSED => 'filled|in:1',
    ];

    protected static $createValidators = [
        Entity::SOURCE,
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

    protected function validateSource(array $input)
    {
        $type = $input[Entity::TYPE];

        $source = $input[Entity::SOURCE];

        $supportedSourceForType = Constants::SUPPORTED_SOURCES[$type];

        if (in_array($source, $supportedSourceForType, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "$source  is not a supported source for type");
        }
    }

    protected function validateTimeRange(array $input)
    {
        $from = $input[Entity::FROM];
        $to = $input[Entity::TO];

        $now = time();

        if ($from > $now)
        {
            throw new Exception\BadRequestValidationFailureException(
                'from cannot be in the future');
        }

        if ($from >= $to)
        {
            throw new Exception\BadRequestValidationFailureException(
                'from cannot be after to');

        }
    }
}
