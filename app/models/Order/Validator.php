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

    public function validateMerchantSpecificData($order, $merchant)
    {
        // TPV - Third Party Validation
        $tpvRequired = $merchant->isTPVRequired();

        if ($tpvRequired)
        {
            if (empty($order->getMethod()))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ORDER_METHOD_REQUIRED_FOR_MERCHANT);
            }

            if (empty($order->getAccountNumber()))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ORDER_ACCOUNT_NUMBER_REQUIRED_FOR_MERCHANT);
            }
        }
    }

}
