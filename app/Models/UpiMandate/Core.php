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
        $this->trace->info(
            TraceCode::UPI_MANDATE_CREATE_REQUEST,
            [
                'customer_id'  => $customer->getPublicId(),
                'merchant_id'  => $this->merchant->getPublicId(),
                'order_id'     => $order->getPublicId(),
                'input'        => $input,
            ]
        );

        $this->transformTokenParamsForUpi($input);

        if (isset($input['start_time']) === false)
        {
            $input['start_time'] = Carbon::now()->getTimestamp();
        }

        if (isset($input['end_time']) === false)
        {
            $input['end_time'] = Carbon::now()->addYears(10)->getTimestamp();
        }

        $upiMandate = (new Entity)->build($input);

        $upiMandate->merchant()->associate($this->merchant);

        $upiMandate->order()->associate($order);

        $upiMandate->customer()->associate($customer);

        $upiMandate->setStatus('created');

        $this->repo->saveOrFail($upiMandate);

        $this->trace->info(
            TraceCode::UPI_MANDATE_CREATED,
            [
                'customer_id'  => $customer->getPublicId(),
                'merchant_id'  => $this->merchant->getPublicId(),
                'order_id'     => $order->getPublicId(),
                'mandate_id'   => $upiMandate->getPublicId(),
            ]
        );

        return $upiMandate;
    }

    public function validateTokenInput($input)
    {
        $this->transformTokenParamsForUpi($input);

        $validator = new Validator();

        $validator->setStrictFalse();

        $validator->validateInput('create', $input);
    }

    // We need to support start_at and expire_at fields being passed by merchant for upi recurring. So, adding this
    // transformer, which will use these params and convert them to the standard start_time and end_time fields.
    protected function transformTokenParamsForUpi(array &$input)
    {
        $endTime = $input['expire_at'] ?? null;

        $startTime = $input['start_at'] ?? null;

        if ($startTime !== null)
        {
            $input[Entity::START_TIME] = $startTime;
        }

        if ($endTime !== null)
        {
            $input[Entity::END_TIME] = $endTime;
        }

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
}
