<?php

namespace RZP\Models\UpiMandate;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;

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
        if ($orderInput[Order\Entity::AMOUNT] > $input[Entity::MAX_AMOUNT])
        {
            throw new Exception\BadRequestValidationFailureException(
                'The order amount cannot be greater than the token max amount for upi recurring',
                Entity::MAX_AMOUNT
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

        $input['recurring_type'] = 'before';

        $input['recurring_value'] = Frequency::$frequencyToRecurringValueMap[$input['frequency']] ?? null;
    }

    // We need to support start_at and expire_at fields being passed by merchant for upi recurring. So, adding this
    // transformer, which will use these params and convert them to the standard start_time and end_time fields.
    protected function transformTokenParamsForUpi(array &$input)
    {
        $startTime = $input['start_at'] ?? Carbon::now()->getTimestamp();

        //Default end time to 10 years from current timestamp.
        $endTime = $input['expire_at'] ?? Carbon::now()->addYears(10)->getTimestamp();

        $input[Entity::START_TIME] = $startTime;

        $input[Entity::END_TIME] = $endTime;

        // We default the frequency to as_presented if merchant does not pass us this parameter.
        $input['frequency'] = $input['frequency'] ?? Frequency::AS_PRESENTED;

        unset($input['start_at']);
        unset($input['expire_at']);

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
        $original = $upiMandate->getOriginal();

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
