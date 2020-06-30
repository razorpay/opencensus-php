<?php

namespace RZP\Models\UpiMandate;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const MAX_AMOUNT_LIMIT = 200000;

    protected static $createRules = [
        Entity::FREQUENCY              => 'required|string|custom',
        Entity::RECURRING_TYPE         => 'required|string|custom',
        Entity::RECURRING_VALUE        => 'required|integer|nullable',
        Entity::MAX_AMOUNT             => 'required|integer|max:200000',
        Entity::RECEIPT                => 'sometimes|nullable|string|max:40',
        Entity::START_TIME             => 'required|epoch',
        Entity::END_TIME               => 'required|epoch',
    ];

    protected static $editRules = [
        Entity::UMN                   => 'sometimes|string',
        Entity::NPCI_TXN_ID           => 'sometimes|string',
        Entity::RRN                   => 'sometimes|string',
        Entity::GATEWAY_REFERENCE_ID  => 'sometimes|string',
    ];

    protected static $createValidators = [
      'time',
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

        $startTime = $input[Entity::START_TIME];

        $endTime = $input[Entity::END_TIME];

        if (($startTime < $currentTime) or ($startTime > $endTime))
        {
            throw new BadRequestValidationFailureException(
                'The start time should be less than end time and greater than current time.'
            );
        }
    }
}
