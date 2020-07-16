<?php

namespace RZP\Models\PayoutDowntime;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{

    const VALID_STATE_CHANGE = [
        Constants::ENABLED   => [Constants::DISABLED],
        Constants::SCHEDULED => [Constants::ENABLED, Constants::CANCELLED]
    ];

    protected static $createRules = [
        Entity::STATUS                => 'required|string|in:Enabled,Scheduled',
        Entity::CHANNEL               => 'required|string|custom',
        Entity::START_TIME            => 'required|epoch',
        Entity::END_TIME              => 'sometimes|required|epoch',
        Entity::DOWNTIME_MESSAGE      => 'required|string',
        Entity::UPTIME_MESSAGE        => 'sometimes|string',
        Entity::ENABLED_EMAIL_OPTION  => 'sometimes|string|in:Yes,No',
        Entity::DISABLED_EMAIL_OPTION => 'sometimes|string|in:Yes,No',
        Entity::CREATED_BY            => 'sometimes|string|max:255',
        Constants::MID_LIST           => 'sometimes|required|array',
    ];

    protected static $editRules = [
        Entity::STATUS                => 'sometimes|required|string|in:Scheduled,Enabled,Disabled,Cancelled',
        Entity::CHANNEL               => 'sometimes|required|string|custom',
        Entity::START_TIME            => 'sometimes|required|epoch',
        Entity::END_TIME              => 'sometimes|required|epoch',
        Entity::DOWNTIME_MESSAGE      => 'sometimes|string',
        Entity::UPTIME_MESSAGE        => 'sometimes|string',
        Entity::ENABLED_EMAIL_OPTION  => 'sometimes|string|in:Yes,No',
        Entity::DISABLED_EMAIL_OPTION => 'sometimes|string|in:Yes,No',
        Entity::CREATED_BY            => 'sometimes|string|max:255',
        Constants::MID_LIST           => 'sometimes|required|array',
    ];

    public function getChannels()
    {
        return [
            Constants::POOL_NETWORK,
            Constants::RBL,
            Constants::ALL,
        ];
    }

    protected function validateChannel(string $attribute, string $channel)
    {
        if (in_array($channel, self::getChannels(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid channel name: ' . $channel);
        }
    }

    public function isStateChangeAllowed(string $currentState, string $newState)
    {
        if ($currentState !== $newState)
        {
            $possibleStateChange = self::VALID_STATE_CHANGE[$currentState] ?? null;

            if (($possibleStateChange === null) or
                in_array($newState, $possibleStateChange) === false)
            {
                throw new Exception\BadRequestValidationFailureException('Invalid state change');
            }
        }
    }

    public function editValidations(Entity $downtime, array $input)
    {
        if(empty($input[Entity::STATUS]) === false)
        {
            $this->isStateChangeAllowed($downtime->getStatus(), $input[Entity::STATUS]);
            $this->checkStateSpecificFieldsProvided($downtime, $input);
        }
    }

    public function checkStateSpecificFieldsProvided(Entity $downtime, array $input)
    {
        if ($input[Entity::STATUS] === Constants::ENABLED)
        {
            if (isset($input[Entity::DOWNTIME_MESSAGE]) === true and
                empty($input[Entity::DOWNTIME_MESSAGE]) === true)
            {
                throw new Exception\BadRequestValidationFailureException('Please provide notification message');
            }

            if (isset($input[Entity::START_TIME]) === true and
                empty($input[Entity::START_TIME]) === true)
            {
                throw new Exception\BadRequestValidationFailureException('Please select notification start time');
            }
        }
        else
        {
            if ($input[Entity::STATUS] === Constants::DISABLED)
            {
                if ((isset($input[Entity::UPTIME_MESSAGE]) === true and
                     empty($input[Entity::UPTIME_MESSAGE]) === true) or
                    (isset($input[Entity::UPTIME_MESSAGE]) === false and
                    empty($downtime->getUpTimeMessage()) === true)
                )
                {
                    throw new Exception\BadRequestValidationFailureException('Please provide notification message');
                }

                if ((isset($input[Entity::END_TIME]) === true and
                     empty($input[Entity::END_TIME]) === true) or
                    (isset($input[Entity::END_TIME]) === false and
                     empty($downtime->getEndTime()) === true)
                )
                {
                    throw new Exception\BadRequestValidationFailureException('Please select notification end time');
                }

                if ((isset($input[Entity::START_TIME]) === true and
                     empty($input[Entity::START_TIME]) === true) or
                    (isset($input[Entity::START_TIME]) === false and
                     empty($downtime->getStartTime()) === true)
                )
                {
                    throw new Exception\BadRequestValidationFailureException('Please select notification start time');
                }
            }
        }
    }

}
