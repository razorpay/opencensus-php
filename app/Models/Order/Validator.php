<?php

namespace RZP\Models\Order;

use RZP\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::AMOUNT          =>  'required|integer|min:100',
        Entity::CURRENCY        =>  'required|size:3|in:INR,USD',
        Entity::RECEIPT         =>  'required|string|max:40',
        Entity::PAYMENT_CAPTURE =>  'sometimes|boolean',
        Entity::CUSTOMER_ID     =>  'sometimes|filled',
        Entity::NOTES           =>  'sometimes|notes',
        Entity::METHOD          =>  'sometimes|in:netbanking',
        Entity::BANK            =>  'sometimes|filled|custom',
        Entity::ACCOUNT_NUMBER  =>  'sometimes|filled|string|max:50|min:5',
        Entity::OFFER_ID        =>  'sometimes|string|size:20'
    );

    protected static $createValidators = [
        Entity::ACCOUNT_NUMBER,
        Entity::AMOUNT,
    ];

    protected function validateAmount($input)
    {
        $maxAmountAllowed = $this->entity->merchant->getMaxPaymentAmount();

        $amount = $input['amount'];

        if ($amount > $maxAmountAllowed)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount exceeds maximum amount allowed.',
                'amount',
                ['amount' => $amount]);
        }
    }

    public function validateOrderNotPaid($order)
    {
        if (($order->getStatus() === Status::PAID) or
            ($order->isAuthorized()))
        {
            // Order already paid for
            throw new Exception\BadRequestException(
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

    public function validateOrderCurrency($order, $currency)
    {
        if ($order->getCurrency() !== $currency)
        {
            // Order and Payment currency mismatch
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_CURRENCY_MISMATCH);
        }
    }

    public function validateMerchantSpecificData($order, $payment = null)
    {
        $this->validateOrderTpvChecks($order, $payment);
    }

    public function validateOrderTpvChecks($order, $payment = null)
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

        $orderBank = $order->getBank();

        $tpvBanks = Netbanking::getSupportedBanksForTPV();

        if (in_array($orderBank, $tpvBanks, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Order bank does not support TPV');
        }

        if ((empty($payment) === false) and
            ($orderBank !== $payment->getBank()))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Order bank does not match the payment bank');
        }

        if (empty($order->getAccountNumber()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_ACCOUNT_NUMBER_REQUIRED_FOR_MERCHANT);
        }
    }

    protected function validateBank($attribute, $bank)
    {
        $supportedBanks = Netbanking::getSupportedBanks();

        if (in_array($bank, $supportedBanks, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_BANK_INVALID);
        }
    }

    /**
     * Custom validator not used as both the entity values are not
     * available at the time of creation.
     * */
    protected function validateAccountNumber(array $input)
    {
        $accountNumberLengths = Netbanking::getAccountNumberLengths();

        if (isset($input[Entity::ACCOUNT_NUMBER]) === false)
        {
            return;
        }

        if (isset($input[Entity::BANK]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BANK_REQUIRED_WITH_ACCOUNT_NUMBER,
                [
                    $input
                ]);
        }

        $bank = $input[Entity::BANK];

        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        if (isset($accountNumberLengths[$bank]) === false)
        {
            return;
        }

        if ($accountNumberLengths[$bank] !== strlen($accountNumber))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_ACCOUNT_NUMBER_INCORRECT_LENGTH,
                [
                    $input
                ]);
        };
    }
}
