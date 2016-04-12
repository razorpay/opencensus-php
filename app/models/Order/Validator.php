<?php

namespace Models\Order;

use Models\Base;
use EE\Exception;
use EE\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'amount'        =>  'required|integer|max:50000000',
        'currency'      =>  'required|size:3|in:INR',
        'receipt'       =>  'required|string|max:40',
        'notes'         =>  'sometimes|notes',
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

    public function validateOrderMethodAndAccount($order, $paymentMethod, $paymentAccount)
    {
        if ((isset($order->getMethod())) and
             ($order->getMethod() !== $paymentMethod))
        {
            // Order and Payment amount mismatch
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_METHOD_MISMATCH);
        }

        if ((isset($order->getAccountId())) and
            ($order->getAccountId() !== $paymentAccount))
        {
            // Order and Payment amount mismatch
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ACCOUNT_MISMATCH);
        }
    }
}
