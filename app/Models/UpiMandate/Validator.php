<?php

namespace RZP\Models\UpiMandate;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Card\IIN\Category;

class Validator extends Base\Validator
{
    const MAX_AMOUNT_LIMIT = 500000;

    const MIN_AMOUNT_LIMIT = 100;

    protected static $createRules = [
        Entity::FREQUENCY              => 'required|string|custom',
        Entity::RECURRING_TYPE         => 'sometimes|string|custom',
        Entity::RECURRING_VALUE        => 'sometimes|integer|nullable',
        Entity::MAX_AMOUNT             => 'required|integer',
        Entity::RECEIPT                => 'sometimes|nullable|string|max:40',
        Entity::START_TIME             => 'sometimes|epoch',
        Entity::END_TIME               => 'sometimes|epoch',
    ];

    protected static $editRules = [
        Entity::UMN          => 'sometimes|string',
        Entity::NPCI_TXN_ID  => 'sometimes|string',
        Entity::RRN          => 'sometimes|string',
        Entity::GATEWAY_DATA => 'sometimes|array',
    ];

    protected static $createValidators = [
        'time',
        'max_amount',
    ];

    protected function validateFrequency(string $attribute, string $value)
    {
        if (Frequency::isValid($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid frequency: ' . $value);
        }
    }

    public function validateRecurringType(string $attribute, string $value)
    {
        if (RecurringType::isValid($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid recurring type: ' . $value);
        }
    }

    public function validateTime($input)
    {
        $currentTime = Carbon::now()->getTimestamp();

        $startTime = $input[Entity::START_TIME] ?? Carbon::now()->getTimestamp();

        $endTime = $input[Entity::END_TIME] ?? Carbon::now()->addYears(10)->getTimestamp();

        if (($startTime < $currentTime) or ($startTime > $endTime))
        {
            throw new BadRequestValidationFailureException(
                'The start time should be less than end time and greater than current time.'
            );
        }
    }

    public function validateMaxAmount($input)
    {
        $amount = $input[Entity::MAX_AMOUNT];

        if ($amount > self::MAX_AMOUNT_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Max amount for UPI recurring payment cannot be greater than Rs. 5000.00',
                Entity::MAX_AMOUNT
            );
        }

        if ($amount < self::MIN_AMOUNT_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Max amount for UPI recurring payment cannot be less than Rs. 1.00',
                Entity::MAX_AMOUNT
            );
        }
    }
}
