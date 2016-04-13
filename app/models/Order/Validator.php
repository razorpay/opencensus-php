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

    // Could have been placed in validator, but there could be more.
    public function validateCategoryRequirement($order, $merchant)
    {
        $category = $merchant->getCategory();

        switch ($category) {
            case 6211:
                $this->validateSecuritiesOrder($order);
                break;

            default:
                break;
        }
    }

    public function validateSecuritiesOrder($order)
    {
        if (empty($order->getMethod()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_CATEGORY_METHOD_REQUIRED);
        }

        if (empty($order->getAccountId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_CATEGORY_ACCOUNT_ID_REQUIRED);
        }
    }
}
