<?php

namespace RZP\Models\Gateway\File;

use RZP\Base;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    const TIME_RANGE = 'time_range';

    protected static $createRules = [
        Entity::TYPE              => 'required|string|max:20|custom',
        Entity::TARGET            => 'required|string|max:50',
        Entity::SENDER            => 'filled|email|max:100',
        Entity::TPV               => 'filled|boolean',
        Entity::RECIPIENTS        => 'filled|array',
        Entity::RECIPIENTS . '.*' => 'email',
        Entity::BEGIN             => 'required|epoch',
        Entity::END               => 'required|epoch',
        Entity::SCHEDULED         => 'filled|boolean',
    ];

    protected static $acknowledgeRules = [
        Entity::PARTIALLY_PROCESSED => 'filled|in:1',
        Entity::COMMENTS            => 'filled|string|max:200',
    ];

    protected static $createValidators = [
        Entity::TARGET,
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

    protected function validateTarget(array $input)
    {
        $type = $input[Entity::TYPE];

        $target = $input[Entity::TARGET];

        if (in_array($target, Constants::SUPPORTED_TARGETS[$type], true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "$target is not a valid target for type $type");
        }
    }

    protected function validateTimeRange(array $input)
    {
        $from = $input[Entity::BEGIN];
        $to = $input[Entity::END];

        $now = Carbon::now()->getTimestamp();

        if ($from > $now)
        {
            throw new Exception\BadRequestValidationFailureException(
                'begin cannot be in the future');
        }

        if ($from >= $to)
        {
            throw new Exception\BadRequestValidationFailureException(
                'begin cannot be after end');

        }
    }
}
