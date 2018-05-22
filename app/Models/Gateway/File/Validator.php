<?php

namespace RZP\Models\Gateway\File;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TYPE              => 'required|string|max:20|custom',
        Entity::TARGET            => 'required|string|max:50',
        Entity::SENDER            => 'filled|email|max:100',
        Entity::SUB_TYPE          => 'filled|string|max:25',
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
        Entity::SUB_TYPE,
        Entity::TIME_RANGE,
    ];

    /**
     * CHecks if the gateway_file entity can be processed based on the below conditions
     * - Entity in acknowledged state can't be processed further
     * - If the processing flag is set to true then it means it is under processing
     *   and cannot be processed
     *
     * @throws Exception\BadRequestException
     */
    public function validateIfProcessable()
    {
        if (($this->entity->isAcknowledged() === true) or
            ($this->entity->isProcessing() === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_FILE_NON_RETRIABLE);
        }
    }

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

    protected function validateSubType(array $input)
    {
        $target = $input[Entity::TARGET];

        $type = $input[Entity::TYPE];

        $subType = $input[Entity::SUB_TYPE] ?? null;

        if (Type::isValidSubTypeForTargetAndType($target, $type, $subType) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "$subType is not a valid subType");
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
