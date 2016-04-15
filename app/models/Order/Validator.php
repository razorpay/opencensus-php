<?php

namespace Models\Order;

use Models\Base;
use Models\Payment;
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
        Entity::METHOD         =>  'sometimes|in:netbanking',
        Entity::ACCOUNT_NUMBER =>  'sometimes|string|max:50|min:5',
    );

    public function validateOrderNotPaid($order)
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

    public function validateMerchantSpecificData($order)
    {
        $this->validateOrderTpvChecks($order);
    }

    public function validateOrderTpvChecks($order)
    {
        // TPV - Third Party Validation
        $tpvRequired = $order->merchant->isTPVRequired();

        if ($tpvRequired === false)
        {
            return;
        }

        if (empty($order->getMethod()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_METHOD_REQUIRED_FOR_MERCHANT);
        }

        if ($order->getMethod() !== Payment\Method::NETBANKING)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Order method needs to be netbanking for the merchant');
        }

        if (empty($order->getAccountNumber()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_ACCOUNT_NUMBER_REQUIRED_FOR_MERCHANT);
        }
    }
}
