<?php

namespace Models\Order;

use Models\Base;
use EE\Exception;
use EE\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::AMOUNT         =>  'required|integer|max:50000000',
        Entity::CURRENCY       =>  'required|size:3|in:INR',
        Entity::RECEIPT        =>  'required|string|max:40',
        Entity::CUSTOMER_ID    =>  'sometimes',
        Entity::NOTES          =>  'sometimes|notes',
        Entity::METHOD         =>  'sometimes',
        Entity::ACCOUNT_NUMBER =>  'sometimes',
    );

    public function validateOrderPaidFor($order)
    {
        if (($order->getStatus() === Status::PAID) or
            ($order->isAuthorized()))
        {
            // Order already paid for
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID);
        }
    }

    public function validateOrderAmount($order, $amount)
    {
        if ($order->getAmount() !== $amount)
        {
            // Order and Payment amount mismatch
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH);
        }
    }

    // Could have been placed in validator, but there could be more.
    public function validateMerchantSpecificData($order, $merchant)
    {
        $tpvRequired = $merchant->isTPVRequired();

        if ($tpvRequired)
        {
            if (empty($order->getMethod()))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ORDER_CATEGORY_METHOD_REQUIRED);
            }

            if (empty($order->getAccountNumber()))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ORDER_CATEGORY_ACCOUNT_ID_REQUIRED);
            }
        }
    }

}
