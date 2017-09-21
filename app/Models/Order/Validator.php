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
        Entity::PAYMENT_CAPTURE =>  'filled|boolean',
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

    /**
     * Given a filled payment entity, validates against this order
     * if the same should be allowed to proceed.
     *
     * @param Payment\Entity $payment
     */
    public function validatePaymentCreation(Payment\Entity $payment)
    {
        $this->validateOrderNotPaid();

        $this->validateOrderAmount($payment->getAdjustedAmountWrtCustFeeBearer());

        $this->validateOrderCurrency($payment->getCurrency());

        $this->validateMerchantSpecificData($payment);
    }

    /**
     * Validates that order is not already paid.
     */
    protected function validateOrderNotPaid()
    {
        $order = & $this->entity;

        // An order is assumed paid if:
        // - status = PAID (Perfect case, amount of order is captured as well)
        //
        // - it doesn't accept partial payments and there is one authorized
        //   payment waiting to be captured by merchant. This we do to avoid
        //   multiple authorized payment against same order.

        // But please not that in case of partial payment, 'authorized' has
        // no sense as there will be multiple authorized payments and we need
        // to continue allow payment creation for rest of the partial payments
        // until status changes to PAID, which is once amount paid = amount.

        $isOrderPaid = ($order->getStatus() === Status::PAID);

        $isOrderAuthorized = (
                                ($order->isAuthorized() === true) and
                                ($order->isPartialPaymentAllowed() === false)
                            );

        if (($isOrderPaid === true) or ($isOrderAuthorized === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID);
        }
    }

    /**
     * Validates given amount against order's amounts to decide if payment
     * creation should be allowed.
     *
     * @param int $paymentAmount
     *
     * @throws Exception\BadRequestException
     */
    protected function validateOrderAmount(int $paymentAmount)
    {
        $orderAmountDue = $this->entity->getAmountDue();

        // In case of partial payment, $paymentAmount <= $orderAmountDue,
        // otherwise it should be same.

        $partialPaymentAllowed = $this->entity->isPartialPaymentAllowed();

        if (($partialPaymentAllowed === false) and
            ($orderAmountDue !== $paymentAmount))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
                'amount',
                [
                    'order_amount'   => $orderAmountDue,
                    'payment_amount' => $paymentAmount,
                ]);
        }

        if (($partialPaymentAllowed === true) and
            ($paymentAmount > $orderAmountDue))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_MORE_THAN_ORDER_AMOUNT_DUE);
        }
    }

    protected function validateOrderCurrency(string $currency)
    {
        if ($this->entity->getCurrency() !== $currency)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_CURRENCY_MISMATCH);
        }
    }

    public function validateMerchantSpecificData(Payment\Entity $payment = null)
    {
        $this->validateOrderTpvChecks($payment);
    }

    protected function validateOrderTpvChecks(Payment\Entity $payment = null)
    {
        $order = $this->entity;

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
