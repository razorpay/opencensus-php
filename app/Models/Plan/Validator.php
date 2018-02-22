<?php

namespace RZP\Models\Plan;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Schedule\Period;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::INTERVAL        => 'required|integer|min:1',
        Entity::PERIOD          => 'required|string|custom',
        Entity::NOTES           => 'sometimes|notes',
        Entity::ITEM_ID         => 'required_without:item|public_id',
        Entity::ITEM            => 'required_without:item_id|array',
    ];

    protected static $createValidators = [
        Entity::INTERVAL
    ];

    protected function validatePeriod($attribute, $value)
    {
        Cycle::validatePeriod($value);
    }

    protected function validateInterval($input)
    {
        $period = $input[Entity::PERIOD];
        $interval = $input[Entity::INTERVAL];

        $maxAllowedInterval = Cycle::getMaxAllowedInterval($period);

        if ($interval > $maxAllowedInterval)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Interval provided exceeds the maximum interval (' . $maxAllowedInterval . ') allowed for the given period (' . $period . ')',
                'interval',
                [
                    'interval'      => $interval,
                    'period'        => $period,
                    'max_allowed'   => $maxAllowedInterval,
                    'input'         => $input
                ]);
        }

        $minAllowedInterval = Cycle::getMinAllowedInterval($period);

        if ($interval < $minAllowedInterval)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Interval provided is less than the minimum interval (' . $minAllowedInterval . ') allowed for the given period (' . $period . ')',
                'interval',
                [
                    'interval'      => $interval,
                    'period'        => $period,
                    'min_allowed'   => $minAllowedInterval,
                    'input'         => $input
                ]);
        }
    }
}
