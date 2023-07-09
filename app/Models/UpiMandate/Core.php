<?php

namespace RZP\Models\UpiMandate;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Constants;

class Core extends Base\Core
{
    public function create(array $input, Order\Entity $order = null, Customer\Entity $customer = null)
    {
        $customerId = ($customer === null ? null : $customer->getPublicId());

        $this->trace->info(
            TraceCode::UPI_MANDATE_CREATE_REQUEST,
            [
                'customer_id'  => $customerId,
                'merchant_id'  => $this->merchant->getPublicId(),
                'order_id'     => $order->getPublicId(),
                'input'        => $input,
            ]
        );

        $this->transformTokenParamsForUpi($input);

        $this->addDefaultsForUpiMandateInput($input);

        $upiMandate = (new Entity)->build($input);

        $upiMandate->merchant()->associate($this->merchant);

        $upiMandate->order()->associate($order);

        $upiMandate->customer()->associate($customer);

        $upiMandate->setStatus('created');

        $this->repo->saveOrFail($upiMandate);

        $this->trace->info(
            TraceCode::UPI_MANDATE_CREATED,
            [
                'customer_id'  => $customerId,
                'merchant_id'  => $this->merchant->getPublicId(),
                'order_id'     => $order->getPublicId(),
                'mandate_id'   => $upiMandate->getPublicId(),
            ]
        );

        return $upiMandate;
    }

    protected function validateOrderAndTokenDetailsForUpiMandate($input, $orderInput)
    {
        if(empty($input[Entity::MAX_AMOUNT]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The max_amount field is mandatory for UPI mandate creation.',
                Entity::MAX_AMOUNT
            );
        }

        if ($orderInput[Order\Entity::AMOUNT] > $input[Entity::MAX_AMOUNT])
        {
            throw new Exception\BadRequestValidationFailureException(
                'The order amount cannot be greater than the token max amount for upi recurring',
                Entity::MAX_AMOUNT
            );
        }

        // Perform validation on fixed frequency on recurring type and recurring value
        if(($this->getFrequency($input) !== Frequency::AS_PRESENTED) and
          ($this->getFrequency($input) !== Frequency::DAILY))
        {
            $this->validateRecurringValue($input);
            $this->validateRecurringTypeAndRecurringValue($input);
        }
    }

    protected function validateRecurringValue($input)
    {
        $frequency = $this->getFrequency($input);
        $recurringValue = $this->getRecurringValue($input);

        switch ($frequency)
        {
            case Frequency::DAILY:
            case Frequency::AS_PRESENTED:
                return;
            case Frequency::WEEKLY:
                if (($recurringValue < 1) or ($recurringValue > 7))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Recurring value should be between 1 and 7 for the provided frequency',
                        Entity::RECURRING_VALUE
                    );
                }
            case Frequency:: BIMONTHLY:
                if (($recurringValue < 1) or ($recurringValue > 15))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Recurring value should be between 1 and 15 for the provided frequency',
                        Entity::RECURRING_VALUE
                    );
                }
            default:
                if (($recurringValue < 1) or ($recurringValue > 31))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Recurring value should be between 1 and 31 for the provided frequency',
                        Entity::RECURRING_VALUE
                    );
                }
        }
    }

    public function validateRecurringTypeAndRecurringValue($input)
    {
        $recurType = $input['recurring_type'];
        $recurVal  = $input['recurring_value'];

        if((empty($recurType) === true) and
            (empty($recurVal) === true))
        {
            return;
        }

        if(((empty($recurType) === true) and (empty($recurVal) === false)) or
            ((empty($recurType) === false) and (empty($recurVal) === true)))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Recurring_type or Recurring_value is missing. Send both the values for successful registration',
                Entity::RECURRING_VALUE
            );
        }

        $frequency = $this->getFrequency($input);

        $currentDay = Carbon::now(Timezone::IST)->day;

        if($frequency === Frequency::WEEKLY)
        {
            $currentDay = Carbon::now(Timezone::IST)->dayOfWeek;
        }

        $isValid = true;
        switch ($recurType)
        {
            case RecurringType::BEFORE:
                $isValid = ($currentDay <= $recurVal);
                break;

            case RecurringType::ON:
                $isValid = ($currentDay == $recurVal);
                break;

            case RecurringType::AFTER:
                $isValid = ($currentDay >= $recurVal);
                break;
        }

        if($isValid === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Recurring type and recurring value is mismatch',
                Entity::RECURRING_VALUE
            );
        }
    }


    public function validateTokenInput($input, $orderInput)
    {
        $this->validateOrderAndTokenDetailsForUpiMandate($input, $orderInput);

        $this->transformTokenParamsForUpi($input);

        $validator = new Validator();

        $validator->setStrictFalse();

        $validator->validateInput('create', $input);
    }

    protected function addDefaultsForUpiMandateInput(array & $input)
    {
        if (isset($input['start_time']) === false)
        {
            $input['start_time'] = Carbon::now()->getTimestamp();
        }

        if (isset($input['end_time']) === false)
        {
            $input['end_time'] = Carbon::now()->addYears(10)->getTimestamp();
        }

        $input['recurring_type'] = $input['recurring_type'] ?? 'before';

        $input['recurring_value'] = $this->getRecurringValue($input);
    }

    protected function getRecurringValue($input)
    {
        return $input['recurring_value'] ?? Frequency::$frequencyToRecurringValueMap[$input['frequency']];
    }

    protected function getFrequency($input): string
    {
        // We default the frequency to as_presented if merchant does not pass us this parameter.
        return $input['frequency'] ?? Frequency::AS_PRESENTED;
    }

    // We need to support start_at and expire_at fields being passed by merchant for upi recurring. So, adding this
    // transformer, which will use these params and convert them to the standard start_time and end_time fields.
    protected function transformTokenParamsForUpi(array &$input)
    {

        $startTime = $input['start_at'] ?? Carbon::now()->getTimestamp();

        $input[Entity::START_TIME] = $startTime;

        unset($input['start_at']);


        if (isset($input[Entity::END_TIME]) === false) {
            //Default end time to 10 years from current timestamp.
            $endTime = $input['expire_at'] ?? Carbon::now()->addYears(10)->getTimestamp();

            $input[Entity::END_TIME] = $endTime;

            unset($input['expire_at']);
        }

        $input['frequency'] = $this->getFrequency($input);

        return $input;
    }

    public function validateUpiMandateForCancel(Entity $upiMandate)
    {
        if ($upiMandate->getStatus() !== Status::CONFIRMED)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_TOKEN_FOR_CANCEL);
        }
    }

    public function validateUpiMandateForPause(Entity $upiMandate)
    {
        // Mandate pause is customer initiated. For these, we directly get callbacks from gateway. As that is the
        // ultimate source of truth, we dont throw an exception. We will consider these callbacks and update the
        // mandate status. If mandate status is not confirmed, we trace such instances to check for inconsistencies.
        if ($upiMandate->getStatus() !== Status::CONFIRMED)
        {
            $this->trace->info(TraceCode::UPI_MANDATE_STATUS_MISMATCH_FOR_PAUSE, [
                'id'     => $upiMandate->getId(),
             'status' => $upiMandate->getStatus(),
            ]);
        }
    }

    public function validateUpiMandateForResume(Entity $upiMandate)
    {
        // Mandate resume is customer initiated. For these, we directly get callbacks from gateway. As that is the
        // ultimate source of truth, we dont throw an exception. We will consider these callbacks and update the
        // mandate status. If mandate status is not confirmed, we trace such instances to check for inconsistencies.
        if ($upiMandate->getStatus() !== Status::PAUSED)
        {
            $this->trace->info(TraceCode::UPI_MANDATE_STATUS_MISMATCH_FOR_RESUME, [
                'id'     => $upiMandate->getId(),
                'status' => $upiMandate->getStatus(),
            ]);
        }
    }

    public function update(Entity $upiMandate): Entity
    {
        $dirty = $upiMandate->getDirty();
        $original = $upiMandate->getRawOriginal();

        $toTrace = [
            'id'              => $upiMandate->getId(),
            'merchant_id'     => $upiMandate->merchant->getId(),
            'token_id'        => $upiMandate->getTokenId(),
            'old_status'      => $original[Entity::STATUS] ?? null,
            'new_status'      => $dirty[Entity::STATUS] ?? null,
        ];

        $this->repo->saveOrFail($upiMandate);

        $this->trace->info(TraceCode::UPI_MANDATE_STATUS_UPDATED, $toTrace);

        return $upiMandate;
    }

    public function validateUpiMandateForCancelCallback(Entity $upiMandate)
    {
        // If mandate cancel is initiated by payer, we directly get callbacks from gateway. As that is the
        // ultimate source of truth, we dont throw an exception. We will consider these callbacks and update the
        // mandate status. If mandate status is not confirmed, we trace such instances to check for inconsistencies.
        if ($upiMandate->getStatus() !== Status::CONFIRMED)
        {
            $this->trace->info(TraceCode::UPI_MANDATE_STATUS_MISMATCH_FOR_CANCEL, [
                'id'     => $upiMandate->getId(),
                'status' => $upiMandate->getStatus(),
            ]);
        }
    }
}
