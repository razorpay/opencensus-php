<?php

namespace RZP\Models\UpiMandate;

use Carbon\Carbon;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\UpiMandate\Status as UpiMandateStatus;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const MAX_AMOUNT_LIMIT = 20000000;

    const NON_BFSI_MAX_AMOUNT_LIMIT = 10000000;

    const BFSI_MAX_AMOUNT_LIMIT = 20000000;

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
        $startTime = Carbon::createFromTimestamp($input[Entity::START_TIME]);

        $endTime = Carbon::createFromTimestamp($input[Entity::END_TIME]);

        if ($startTime > $endTime)
        {
            throw new BadRequestValidationFailureException(
                'The start time should be less than end time',
                null,
                [
                    'start_time'       => $startTime,
                    'end_time'         => $endTime,
                ]);
        }

        $validationTime = Carbon::now()->addYears(30)->addMinutes(1)->timestamp;
        if ($input[Entity::END_TIME] > $validationTime)
        {
            throw new BadRequestValidationFailureException(
                'expire_at cannot be more than 30 years for upi'
            );
        }
    }

    public function validateMaxAmount($input)
    {
        $amount = $input[Entity::MAX_AMOUNT];

        if ($amount > self::MAX_AMOUNT_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Max amount for UPI recurring payment cannot be greater than Rs. 200000.00',
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

    /**
     * @param Entity $upiMandate
     *
     * @throws BadRequestException
     */
    public function validateUpiMandateStatus(Entity $upiMandate)
    {
        //mandate status should not be paused or revoked.
        if($upiMandate->getStatus() === UpiMandateStatus::PAUSED)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MANDATE_PAUSED);
        }

        if($upiMandate->getStatus() === UpiMandateStatus::REVOKED)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MANDATE_REVOKED);
        }
    }
}
