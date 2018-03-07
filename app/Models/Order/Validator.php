<?php

namespace RZP\Models\Order;

use RZP\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Currency\Currency;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::AMOUNT          => 'required|integer|min:0',
        Entity::CURRENCY        => 'required|size:3|in:INR,USD',
        Entity::RECEIPT         => 'sometimes|nullable|string|max:40',
        Entity::PAYMENT_CAPTURE => 'filled|boolean',
        Entity::CUSTOMER_ID     => 'sometimes|filled',
        Entity::NOTES           => 'sometimes|notes',
        Entity::METHOD          => 'sometimes|in:netbanking,emandate',
        Entity::BANK            => 'sometimes|filled',
        Entity::ACCOUNT_NUMBER  => 'sometimes|filled|string|max:50|min:5',
        Entity::DISCOUNT        => 'sometimes|boolean',
        Entity::OFFER_ID        => 'sometimes|string|size:20',
    );

    protected static $createValidators = [
        Entity::ACCOUNT_NUMBER,
        Entity::AMOUNT,
        Entity::BANK,
        'method_fee_bearer',
        Entity::CURRENCY,
        'offer',
    ];

    protected function validateAmount($input)
    {
        $amount = $input['amount'];

        if ((isset($input[Entity::METHOD]) === false) or
            ($input[Entity::METHOD] !== Payment\Method::EMANDATE))
        {
            if ($amount < 100)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The amount must be at least 100.',
                    Entity::AMOUNT,
                    [Entity::AMOUNT => $amount]);
            }
        }
        else if ($input[Entity::METHOD] === Payment\Method::EMANDATE)
        {
            //
            // Note that an emandate payment order can be created for second recurring also.
            // Hence, we cannot enforce 0rs for ALL emandate payment orders.
            //
            if ((isset($input[Entity::BANK]) === true) and
                (Payment\Gateway::isZeroRupeeFlowSupported($input[Entity::BANK]) === false) and
                ($amount < 100))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The amount must be at least 100.',
                    Entity::AMOUNT,
                    [Entity::AMOUNT => $amount]);
            }
        }

        $maxAmountAllowed = $this->entity->merchant->getMaxPaymentAmount();

        if ($amount > $maxAmountAllowed)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount exceeds maximum amount allowed.',
                Entity::AMOUNT,
                [Entity::AMOUNT => $amount]);
        }
    }

    protected function validateCurrency($input)
    {
        if (isset($input[Entity::METHOD]) === false)
        {
            return;
        }

        $currency = $input[Entity::CURRENCY];
        $method = $input[Entity::METHOD];

        if (in_array($method, [Payment\Method::NETBANKING, Payment\Method::EMANDATE], true))
        {
            if ($currency !== Currency::INR)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The currency should be INR when method is ' . $method);
            }
        }
    }

    protected function validateMethodFeeBearer($input)
    {
        if (isset($input[Entity::METHOD]) === false)
        {
            return;
        }

        if ($input[Entity::METHOD] === Payment\Method::EMANDATE)
        {
            $merchant = $this->entity->merchant;

            if ($merchant->isFeeBearerCustomer() === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Order creation failed. Please contact Razorpay for further assistance.');
            }
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

        $this->validateAutoCapture($payment);

        // TPV Check is done before check for generic order payment match.
        $this->validateMerchantSpecificData($payment);

        $this->validateOrderBank($payment->getBank());

        $this->validateOrderMethod($payment->getMethod());
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

        $isDiscounted = $this->entity->isDiscountApplicable();

        if (($partialPaymentAllowed === false) and
            ($isDiscounted === false) and
            ($orderAmountDue !== $paymentAmount))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
                Entity::AMOUNT,
                [
                    'order_amount'   => $orderAmountDue,
                    'payment_amount' => $paymentAmount,
                ]);
        }

        if ($isDiscounted === true)
        {
            $discountedAmount = $this->entity->offer->getDiscountedAmount($orderAmountDue);

            if ($discountedAmount !== $paymentAmount)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
                    Entity::AMOUNT,
                    [
                        'discounted_amount' => $discountedAmount,
                        'payment_amount'    => $paymentAmount,
                    ]);
            }
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

    protected function validateOrderBank($bank)
    {
        if (($this->entity->getBank() !== null) and
            ($this->entity->getBank() !== $bank))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_BANK_DOES_NOT_MATCH_PAYMENT_BANK);
        }
    }

    public function validateMerchantSpecificData(Payment\Entity $payment = null)
    {
        $this->validateOrderTpvChecks($payment);
    }

    public function validateAutoCapture(Payment\Entity $payment)
    {
        $order = $this->entity;

        if (($payment->isEmandate() === true) and
            ($order->getPaymentCapture() === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'payment_capture should be true for eMandate payments.',
                Entity::PAYMENT_CAPTURE,
                [
                    'payment_id' => $payment->getId(),
                    'method' => $payment->getMethod(),
                    'auth_type' => $payment->getAuthType(),
                    'order_id'  => $order->getId(),
                ]);
        }
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

        if (empty($order->getMethod()) === true)
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

    protected function validateBank($input)
    {
        if (isset($input[Entity::BANK]) === false)
        {
            return;
        }

        $supportedBanks = Netbanking::getSupportedBanks();

        // @fixme: make it generic
        if ((isset($input['method']) === true) and
            ($input['method'] === Payment\Method::EMANDATE))
        {
            $supportedBanks = Payment\Gateway::getAllEMandateBanks();
        }

        $bank = $input[Entity::BANK];

        if (in_array($bank, $supportedBanks, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_BANK_INVALID);
        }
    }

    /**
     * Custom validator not used as both the entity values are not
     * available at the time of creation.
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
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
                Entity::BANK,
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
                Entity::ACCOUNT_NUMBER,
                [
                    $input
                ]);
        };
    }

    protected function validateOrderMethod(string $method = null)
    {
        $order = $this->entity;

        if (($order->getMethod() !== null) and
            ($order->getMethod() !== $method))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_METHOD_DOES_NOT_MATCH_ORDER_METHOD);
        }
    }

    protected function validateOffer($input)
    {
        if (isset($input[Entity::DISCOUNT]) === false)
        {
            return;
        }

        if (($input[Entity::DISCOUNT] === true) and
            (isset($input[Entity::OFFER_ID]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Discount without offer_id is not supported');
        }
    }
}
