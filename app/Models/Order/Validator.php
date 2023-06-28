<?php

namespace RZP\Models\Order;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\BankAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Currency\Core as CurrencyCore;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\SubscriptionRegistration\Validator as SubscriptionRegistrationValidator;
use RZP\Trace\TraceCode;

class Validator extends Base\Validator
{
    protected $trace;

    /**
     * @var Merchant\Entity
     */
    public $merchant;

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->merchant = $app['basicauth']->getMerchant();
    }

    protected static $createRules = [
        Entity::AMOUNT                             => 'required|integer|min:0',
        Entity::FIRST_PAYMENT_MIN_AMOUNT           => 'sometimes|nullable|integer|min_amount',
        Entity::CURRENCY                           => 'required|string|size:3',
        Entity::RECEIPT                            => 'sometimes|nullable|string|max:40',
        Entity::PAYMENT_CAPTURE                    => 'filled|boolean',
        Entity::CUSTOMER_ID                        => 'filled|public_id|size:19',
        Entity::NOTES                              => 'sometimes|notes',
        Entity::METHOD                             => 'sometimes|in:netbanking,emandate,upi,nach,card',
        Entity::BANK                               => 'filled',
        Entity::DISCOUNT                           => 'sometimes|boolean',
        Entity::OFFERS                             => 'sometimes|array',
        Entity::BANK_ACCOUNT                       => 'sometimes|array',
        Entity::BANK_ACCOUNT
        . '.' . BankAccount\Entity::NAME           => 'sometimes|max:120|string',
        Entity::BANK_ACCOUNT
        . '.' . BankAccount\Entity::IFSC           => 'required_with:bank_account|alpha_num|size:11',
        Entity::BANK_ACCOUNT
        . '.' . BankAccount\Entity::ACCOUNT_NUMBER => 'required_with:bank_account|alpha_num|between:5,35',
        Entity::OFFERS . '*'                       => 'filled|public_id|size:20',
        Entity::FORCE_OFFER                        => 'filled|boolean',
        Entity::PARTIAL_PAYMENT                    => 'sometimes|boolean',
        Entity::PAYMENT                            => 'sometimes|array',
        Entity::CHECKOUT_CONFIG_ID                 => 'filled|size:14',
        Entity::PHONEPE_SWITCH_CONTEXT             => 'sometimes|json|max:3000',
        Entity::PRODUCT_ID                         => 'required_with:product_type|alpha_num|size:14',
        Entity::PRODUCT_TYPE                       => 'required_with:product_id|string|max:32|custom',
        Entity::APP_OFFER                          => 'sometimes|boolean',
        Entity::PRODUCTS                           => 'sometimes|array|max:128',
        Entity::TAX_INVOICE                        => 'sometimes|array',
        Entity::CONVENIENCE_FEE_CONFIG             => 'sometimes|nullable|array'
    ];

    protected static $createValidators = [
        Entity::ACCOUNT_NUMBER,
        Entity::BANK,
        Entity::CURRENCY,
        Entity::AMOUNT,
        'method_fee_bearer',
        Entity::DISCOUNT,
    ];

    protected static $editRules = [
        Entity::NOTES                       => 'sometimes|notes',
        Entity::PARTIAL_PAYMENT             => 'sometimes|boolean|custom',
        Entity::FIRST_PAYMENT_MIN_AMOUNT    => 'sometimes|integer|min_amount|custom',
    ];

    protected static $internalEditRules = [
        Entity::AMOUNT_PAID            => 'sometimes|integer',
        Entity::STATUS                 => 'sometimes|string',
        Entity::ATTEMPTS               => 'sometimes|integer',
        Entity::AUTHORIZED             => 'sometimes|boolean',
        Entity::MERCHANT_ID            => 'required|string',
    ];

    protected static $minAmountCheckRules = [
        Entity::AMOUNT => 'required|integer|min_amount'
    ];

    protected $changeBankCodeGatewayMapping = [
        IFSC::UJVN => 'USFB',
    ];

    /**
     * @var string[] Validation rules for fetching order
     *
     * @see Service::fetchOrderDetailsForCheckout()
     */
    protected static $fetchOrderDetailsForCheckoutRules = [
        'expand'   => 'sometimes|array|in:order',
        'order_id' => 'required_if:order,null|public_id',
        'order'    => 'required_if:order_id,null|array',
        'subscription_id' => 'sometimes|string|public_id',
    ];

    protected function getBankCodeMapping($bank)
    {
        if (array_key_exists($bank, $this->changeBankCodeGatewayMapping) === true)
        {
            $bank = $this->changeBankCodeGatewayMapping[$bank];
        }

        return $bank;
    }

    protected function validatePartialPayment($attribute, $value)
    {
        $this->validatePartialPaymentUpdateAllowed();
    }

    protected function validateFirstPaymentMinAmount($attribute, $minAmount)
    {
        $this->validatePartialPaymentUpdateAllowed();

        $order = $this->entity;

        $amountDue = $order->getAmountDue();

        if (($minAmount >= $amountDue) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'minimum amount should be less than '.$amountDue,
                Entity::AMOUNT_DUE,
                [Entity::AMOUNT_DUE => $amountDue]);
        }
    }

    protected function validatePartialPaymentUpdateAllowed()
    {
        $order = $this->entity;

        if ($order->getProductType() !== ProductType::PAYMENT_LINK_V2)
        {
            throw new Exception\BadRequestValidationFailureException(
                'partial payment update not allowed',
                Entity::PRODUCT_TYPE,
                [Entity::PRODUCT_TYPE => $order->getProductType()]);
        }

        if ($order->getStatus() !== Status::CREATED)
        {
            throw new Exception\BadRequestValidationFailureException(
                'partial payment update not allowed',
                Entity::STATUS,
                [Entity::STATUS => $order->getStatus()]);
        }
    }

    public function validateAmount($input)
    {
        $amount = $input['amount'];

        $merchant = $this->merchant;

        if ($merchant === null)
        {
            $merchant = $this->entity->merchant;
        }

        if ((isset($input[Entity::METHOD]) === false) or
            (($input[Entity::METHOD] !== Payment\Method::EMANDATE) and
                ($input[Entity::METHOD] !== Payment\Method::NACH)))
        {
            $this->validateInputValues('min_amount_check', $input);
        }
        else if ($input[Entity::METHOD] === Payment\Method::EMANDATE)
        {
            //
            // Note that an emandate payment order can be created for second recurring also.
            // Hence, we cannot enforce 0rs for ALL emandate payment orders.
            //
            if ((isset($input[Entity::BANK]) === true) and
                (Payment\Gateway::isZeroRupeeFlowSupported($input[Entity::BANK]) === false))
            {
                $this->validateInputValues('min_amount_check', $input);
            }
        }

        $domesticMaxAmountAllowed = $merchant->getMaxPaymentAmount();

        $internationalMaxAmountAllowed = $merchant->getMaxPaymentAmountTransactionType(true);

        // order creation will use max of domestic/international limit.
        // Payment will have individual validation.
        $maxAmountAllowed = max($domesticMaxAmountAllowed,$internationalMaxAmountAllowed);

        $currency = $input['currency'];

        $baseAmount = $amount;

        if ($currency != $merchant->getCurrency())
        {
            $baseAmount = (new CurrencyCore)->getBaseAmount($amount, $currency, $merchant->getCurrency());
        }

        if (($baseAmount > $maxAmountAllowed) === true)
        {
            $this->trace->count(Metric::ORDER_CREATION_AMOUNT_VALIDATION_FAILURE_COUNT, [
                'business_type' => $merchant->merchantDetail->getBusinessType() ?? "",
            ]);

            throw new Exception\BadRequestValidationFailureException(
                'Amount exceeds maximum amount allowed.',
                Entity::AMOUNT,
                [Entity::AMOUNT => $amount]);
        }
    }

    public function validateCurrency($input)
    {
        $currency = $input[Entity::CURRENCY];

        $merchant = $this->merchant;

        if ($merchant === null)
        {
            $merchant = $this->entity->merchant;
        }

        // if currency conversion is not enabled allow only INR
        // if currency conversion is enabled, it should be a valid currency
        if ((($merchant->convertOnApi() === null) and
            ($currency !== $merchant->getCurrency())) or
            (in_array($currency, Currency::SUPPORTED_CURRENCIES, true) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_CURRENCY_NOT_SUPPORTED,
                'currency');
        }

        // if method is not defined, dont validate currency
        if (isset($input[Entity::METHOD]) === false)
        {
            return;
        }

        $method = $input[Entity::METHOD];

        if (($method !== Payment\Method::CARD) and
            ($currency !== Currency::INR))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The currency should be INR when method is ' . $method);
        }
    }

    protected function validateMethodFeeBearer($input)
    {
        if (isset($input[Entity::METHOD]) === false)
        {
            return;
        }

        if (($input[Entity::METHOD] === Payment\Method::EMANDATE) or
            ($input[Entity::METHOD] === Payment\Method::NACH))
        {
            $merchant = $this->entity->merchant;

            if ($merchant->isFeeBearerCustomerOrDynamic() === true)
            {
                $errorMessage = $input[Entity::METHOD] . ' is not supported for customer fee bearer model. Please contact support for more details.';
                throw new Exception\BadRequestValidationFailureException(
                    $errorMessage,
                    Entity::METHOD,
                    [
                        'method'        =>  $input[Entity::METHOD],
                        'fee_bearer'    =>  $merchant->fee_bearer,
                    ]
                );
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

        $this->validateOrderAmount($payment);

        $this->validateOrderCurrency($payment->getCurrency());

        $this->validateAutoCapture($payment);

        // TPV Check is done before check for generic order payment match.
        $this->validateMerchantSpecificData($payment);

        $this->validateOrderBank($payment->getBank());

        $this->validateOrderMethod($payment->getMethod());

        $this->validateOrderForNachMethod();
    }

    public function validateOrderForNachMethod()
    {
        if ($this->entity->getMethod() !== SubscriptionRegistration\Method::NACH)
        {
            return;
        }

        $payments = $this->entity->payments;

        foreach ($payments as $payment)
        {
            if (($payment->isFailed() !== true) and
                ($payment->isRecurringTypeAuto() === false))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_NACH_FORM_STATUS_PENDING);
            }
        }
    }

    public function validateNachStatusForCheckout(Entity $order): void
    {
        if ($order->getMethod() !== Payment\Method::NACH) {
            return;
        }

        $invoice = $order->invoice;

        if ($invoice === null) {
            return;
        }

        (new SubscriptionRegistrationValidator())->validateInvoiceCreatedForTokenRegistration($invoice);

        $subscriptionRegistration = $invoice->tokenRegistration;

        (new SubscriptionRegistrationValidator())->validateSubscriptionRegistrationForAuthentication($subscriptionRegistration);
    }

    /**
     * Validates that order is not already paid.
     */
    public function validateOrderNotPaid()
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
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID,
                null,
                [
                    'order_id' => $order->getId(),
                ]);
        }
    }

    /**
     * Validates given amount against order's amounts to decide if payment
     * creation should be allowed.
     *
     * @param Payment\Entity $payment
     *
     *
     * @throws Exception\BadRequestException
     */
    protected function validateOrderAmount(Payment\Entity $payment )
    {
        $paymentAmount = $payment->getAdjustedAmountWrtCustFeeBearer();

        /** @var Entity $order */
        $order = $this->entity;

        //Reducing convenience fee and convenience fee gst
        //from payment amount to get the expected order amount

        $paymentAmount = $payment->getAmountWithoutConvenienceFeeIfApplicable($paymentAmount, $order);

        // In case of partial payment, $paymentAmount <= $orderAmountDue,
        // otherwise it should be same.

        $partialPaymentAllowed = $order->isPartialPaymentAllowed();

        $orderAmountDue = $order->getAmountDue($payment);

        if (($partialPaymentAllowed === false) and
            ($orderAmountDue !== $paymentAmount))
        {
            if ($payment->getMethod() === 'bank_transfer')
            {

                if ($paymentAmount <= 0)
                {

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MINIMUM_ALLOWED_AMOUNT,
                        Entity::AMOUNT,
                        [
                            'order_amount' => $orderAmountDue,
                            'payment_amount' => $paymentAmount,
                        ]);
                }

                if (($order->merchant->isFeatureEnabled(Feature\Constants::EXCESS_ORDER_AMOUNT) === false) and
                    ($orderAmountDue < $paymentAmount))
                {

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
                        Entity::AMOUNT,
                        [
                            'order_amount' => $orderAmountDue,
                            'payment_amount' => $paymentAmount,
                        ]);
                }

                if (($order->merchant->isFeatureEnabled(Feature\Constants::ACCEPT_LOWER_AMOUNT) === false) and
                    ($orderAmountDue > $paymentAmount))
                {

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
                        Entity::AMOUNT,
                        [
                            'order_amount' => $orderAmountDue,
                            'payment_amount' => $paymentAmount,
                        ]);
                }
            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
                    Entity::AMOUNT,
                    [
                        'order_amount'   => $orderAmountDue,
                        'payment_amount' => $paymentAmount,
                    ]);
            }
        }

        if ($partialPaymentAllowed === true)
        {
            $this->validatePartialPaymentOrderAmount($paymentAmount, $payment);
        }
    }

    protected function validatePartialPaymentOrderAmount(int $paymentAmount, Payment\Entity $payment)
    {
        /** @var Entity $order */
        $order = $this->entity;

        $amountDue             = $order->getAmountDue();
        $amountPaid            = $order->getAmountPaid();
        $isFirstPayment        = ($amountPaid === 0);
        $firstPaymentMinAmount = $order->getFirstPaymentMinAmount();

        if (($isFirstPayment === true) and
            ($firstPaymentMinAmount !== null) and
            ($paymentAmount < $firstPaymentMinAmount))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MINIMUM_ALLOWED_AMOUNT,
                Entity::AMOUNT);
        }

        if (($paymentAmount > $amountDue) and
            ($order->merchant->isFeatureEnabled(Feature\Constants::EXCESS_ORDER_AMOUNT) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_MORE_THAN_ORDER_AMOUNT_DUE);
        }

        if ($amountDue <= 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID);
        }

        if ($amountPaid + $paymentAmount > $order->merchant->getMaxPaymentAmountTransactionType($payment->isInternational()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_TXN_LIMIT_EXCEEDED);
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
        // bypassing upi as payment create fails for tpv merchants because the bank codes we receive are different
        // from payment entity than what we have in order entity.In case of UPI TPV, the validation is actually done by
        // the acquiring bank using Account Number and IFSC code. We don't really need the 'bank' field.

        $order = $this->entity;

        $tpvRequired = $order->merchant->isTPVRequired();
        $method = $order->getMethod();

        if (($tpvRequired === true) and
            ($method === Payment\Method::UPI))
        {
            return;
        }

        if ($method === Payment\Method::EMANDATE)
        {
            $bank = $this->getBankCodeMapping($bank);
        }

        if (($order->getBank() !== null) and
            ($order->getBank() !== $bank))
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
            ($order->getPaymentCapture() !== true))
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

        $method = $order->getMethod();

        if (($method !== null) and
            ($method !== Payment\Method::NETBANKING) and
            ($method !== Payment\Method::UPI))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Order method needs to be netbanking or upi for the merchant');
        }

        $orderBank = $order->getBank();

        $tpvBanks = Netbanking::getSupportedBanksForTPV();

    // bypassing upi as payment create fails for tpv merchants because the bank codes we receive are different
    // from payment entity than what we have in order entity.In case of UPI TPV, the validation is actually done by
    // the acquiring bank using Account Number and IFSC code. We don't really need the 'bank' field.

        if (($method !== null and
            $method === Payment\Method::NETBANKING) and
            (in_array($orderBank, $tpvBanks, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Order bank does not support TPV');
        }

        if ((empty($payment) === false and
             $method === Payment\Method::NETBANKING) and
            ($orderBank !== $payment->getBank()))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Order bank does not match the payment bank');
        }

        // TODO: Change this after creating bank account entities for all the previous TPV orders
        $accountNumber = empty($order->bankAccount) === true ? $order->getAccountNumber() : $order->bankAccount->getAccountNumber();

        if (empty($accountNumber) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_ACCOUNT_NUMBER_REQUIRED_FOR_MERCHANT);
        }
    }

    public function validateBank($input)
    {
        if (isset($input[Entity::BANK]) === false)
        {
            return;
        }

        $method = isset($input['method']) ? $input['method'] : null;

        switch ($method)
        {
            case Payment\Method::EMANDATE:
                $supportedBanks = Payment\Gateway::getAllEMandateBanks();

                if ((string)$input[Entity::AMOUNT] === '0')
                {
                    $supportedBanks = Payment\Gateway::removeEmandateRegistrationDisabledBanks($supportedBanks);

                    $auth_type = $input['auth_type'] ?? null;

                    if($auth_type === "netbanking")
                    {
                        $supportedBanks = Payment\Gateway::removeNetbankingEmandateRegistrationDisabledBanks($supportedBanks);
                    }
                }
                break;

            case Payment\Method::UPI:
                $supportedBanks = Payment\Processor\Upi::getAllUpiBanks();
                break;

            case Payment\Method::NETBANKING:
                $supportedBanks = $this->merchant->methods->getSupportedBanks();
                break;
            default:
                $supportedBanks = Netbanking::getSupportedBanks();
        }

        $bank = $input[Entity::BANK];

        if (($method === Payment\Method::NETBANKING) and
            (in_array($bank, $supportedBanks, true) === false) and
            ((Netbanking::isSupportedBank($bank) === true)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_BANK_NOT_ENABLED_FOR_MERCHANT);
        }

        if (in_array($bank, $supportedBanks, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_BANK_INVALID);
        }
    }

    public function validateLineItemsCount(int $lineItemsCount)
    {
        if ($lineItemsCount > 25)
        {
            $message = 'The order may not have more than ' . 25 . ' items in total.';

            throw new BadRequestValidationFailureException($message);
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

        if ((isset($input[Entity::BANK_ACCOUNT]) === false) or
            (isset($input[Entity::BANK_ACCOUNT][Entity::ACCOUNT_NUMBER]) === false))
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

    protected function validateDiscount($input)
    {
        if (isset($input[Entity::DISCOUNT]) === false)
        {
            return;
        }

        if ((boolval($input[Entity::DISCOUNT]) === true) and
            (empty($input[Entity::OFFERS]) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                    'Discount without offers is not supported');
        }
    }

    public function validateProductType($attribute, $value)
    {
        ProductType::checkType($value);
    }

}
