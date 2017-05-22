<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;

use RZP\Base;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    //
    // We have rules on create and update for the two status: DRAFT, ISSUED.
    // Eg. In ISSUED state, you cannot update amount of the invoice. There are
    //     rules to accommodate such requirements. This way it's good to manage and
    //     is easy to understand.
    //
    // - Create invoice in DRAFT status
    // - Create invoice in ISSUED status
    // - Update invoice when it's in DRAFT status
    // - Update invoice when it's in ISSUED status
    //

    const CREATE_DRAFT  = 'createDraft';
    const CREATE_ISSUED = 'createIssued';
    const EDIT_DRAFT    = 'editDraft';
    const EDIT_ISSUED   = 'editIssued';

    const MAX_ALLOWED_LINE_ITEMS = 20;

    //
    // A minimum of 15 minutes of gap must exist between invoice issue and expired by
    //
    const MIN_EXPIRY_SECS = 900;

    protected static $createRules = [
        // Entity::DISCOUNT_FLAT       => 'sometimes|integer|min:1',
        // Entity::DISCOUNT_PERCENT    => 'sometimes|integer|min:1|max:100',
        // Entity::ADJUSTMENT          => 'sometimes|integer',
        // Entity::SHIPPING            => 'sometimes|integer|min:1',

        // Entity::DUE_BY              => 'sometimes|integer',
        // Entity::SCHEDULED_AT        => 'sometimes|integer',

        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|epoch',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40',
        Entity::VIEW_LESS           => 'sometimes|in:1',
        Entity::SOURCE              => 'sometimes|string|max:32|custom',
        Entity::TYPE                => 'sometimes|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19',
        Entity::LINE_ITEMS          => 'sometimes|array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'sometimes|boolean',
        Entity::AMOUNT              => 'sometimes|integer|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'sometimes|in:INR',
        Entity::BILLING_START       => 'sometimes|epoch',
        Entity::BILLING_END         => 'sometimes|epoch',
        Entity::USER_ID             => 'sometimes|alpha_num|size:14',
        Entity::DRAFT               => 'sometimes|boolean',
        Entity::EXPIRE_BY           => 'sometimes|epoch',
    ];

    //
    // Following is redundant and same as $createRules but keeping it as it keeps code
    // at other places clean
    //

    protected static $createDraftRules = [
        // Entity::DISCOUNT_FLAT       => 'sometimes|integer|min:1',
        // Entity::DISCOUNT_PERCENT    => 'sometimes|integer|min:1|max:100',
        // Entity::ADJUSTMENT          => 'sometimes|integer',
        // Entity::SHIPPING            => 'sometimes|integer|min:1',

        // Entity::DUE_BY              => 'sometimes|integer',
        // Entity::SCHEDULED_AT        => 'sometimes|integer',

        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|epoch',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40',
        Entity::VIEW_LESS           => 'sometimes|in:1',
        Entity::SOURCE              => 'sometimes|string|max:32|custom',
        Entity::TYPE                => 'sometimes|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19',
        Entity::LINE_ITEMS          => 'sometimes|array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'sometimes|boolean',
        Entity::AMOUNT              => 'sometimes|integer|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'sometimes|in:INR',
        Entity::BILLING_START       => 'sometimes|epoch',
        Entity::BILLING_END         => 'sometimes|epoch',
        Entity::USER_ID             => 'sometimes|alpha_num|size:14',
        Entity::DRAFT               => 'sometimes|boolean',
        Entity::EXPIRE_BY           => 'sometimes|epoch',
    ];

    protected static $createIssuedRules = [
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|epoch',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40',
        Entity::VIEW_LESS           => 'sometimes|in:1',
        Entity::SOURCE              => 'sometimes|string|max:32|custom',
        Entity::TYPE                => 'sometimes|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19',
        Entity::LINE_ITEMS          => 'sometimes|array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'sometimes|boolean',
        Entity::AMOUNT              => 'sometimes|integer|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'sometimes|in:INR',
        Entity::BILLING_START       => 'sometimes|epoch',
        Entity::BILLING_END         => 'sometimes|epoch',
        Entity::USER_ID             => 'sometimes|alpha_num|size:14',
        Entity::DRAFT               => 'sometimes|in:0',
        Entity::EXPIRE_BY           => 'sometimes|epoch',
    ];

    protected static $editDraftRules  = [
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|epoch',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40',
        Entity::CUSTOMER            => 'sometimes',
        Entity::CUSTOMER_ID         => 'sometimes|string|size:19',
        Entity::LINE_ITEMS          => 'sometimes|array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'sometimes|boolean',
        Entity::AMOUNT              => 'sometimes|integer|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::BILLING_START       => 'sometimes|epoch',
        Entity::BILLING_END         => 'sometimes|epoch',
        Entity::EXPIRE_BY           => 'sometimes|epoch',
        Entity::DRAFT               => 'sometimes|boolean',
    ];

    protected static $editIssuedRules  = [
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40',
    ];

    //
    // Custom validators.
    //

    protected static $createValidators =[
        Entity::PARTIAL_PAYMENT,
        Entity::AMOUNT,
        Entity::CURRENCY,
    ];

    protected static $createIssuedValidators = [
        Entity::CURRENCY,
    ];

    protected static $editDraftValidators = [
        Entity::PARTIAL_PAYMENT,
        Entity::AMOUNT,
        self::EDIT_DRAFT . Entity::AMOUNT,
    ];

    public function validateAmount(array $input)
    {
        if (isset($input[Entity::AMOUNT]) === false)
        {
            return;
        }

        $this->checkIfAmountIsExpectedInInput($input);

        $this->validateMaxAllowedAmount($input[Entity::AMOUNT]);
    }

    /**
     * Validates partial_payment input is sent only for type invoice.
     *
     * @param array $input
     */
    public function validatePartialPayment(array $input)
    {
        if (isset($input[Entity::PARTIAL_PAYMENT]) === false)
        {
            return;
        }

        $type = $input[Entity::TYPE] ?? $this->entity->getType();

        if ($type !== Type::INVOICE)
        {
            throw new BadRequestValidationFailureException(
                'partial_payment is not expected with link type');
        }
    }

    /**
     * Checks if amount is expected in input key.
     * Rules:
     * - Amount should only be sent in input for ecod or link types.
     * - Amount should not be sent if line_items are being sent with above types.
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    private function checkIfAmountIsExpectedInInput(array $input)
    {
        $type = $input[Entity::TYPE] ?? $this->entity->getType();

        if ($type === null)
        {
            $type = Type::INVOICE;
        }

        if ($type === Type::INVOICE)
        {
            throw new BadRequestValidationFailureException(
                'amount can be only sent for ecod or link types.'
            );
        }

        if (isset($input[Entity::LINE_ITEMS]) === true)
        {
            throw new BadRequestValidationFailureException(
                'amount should not be sent if line_items are being sent in the input.'
            );
        }
    }

    /**
     * Checks if amount is lesser than max payment amount allowed for merchant.
     * This method also gets called from other flow when line_items are getting
     * added/updated/removed. At that time too we need to check for the following.
     *
     * @param int $amount
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateMaxAllowedAmount(int $amount)
    {
        $invoice = $this->entity;

        $maxAmountAllowed = $invoice->merchant->getMaxPaymentAmount();

        if ($amount > $maxAmountAllowed)
        {
            throw new BadRequestValidationFailureException(
                'Invoice amount exceeds maximum payment amount allowed.',
                'amount',
                [
                    'id'                 => $invoice->getId(),
                    'amount'             => $amount,
                    'max_amount_allowed' => $maxAmountAllowed,
                ]);
        }
    }

    /**
     * Currency is optional (defaults to INR) but in laravel 5.2, if sent null
     * no other validations would happen and will attempt to flush null in db.
     * Ref: https://laravel.com/docs/5.2/validation#rule-string
     * To avoid that, adding validator to be run by spine here.
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateCurrency(array $input)
    {
        if (array_key_exists(Entity::CURRENCY, $input) === false)
        {
            return;
        }

        if (empty($input[Entity::CURRENCY]))
        {
            throw new BadRequestValidationFailureException(
                'Currency must not be empty.',
                Entity::CURRENCY,
                $input
            );
        }
    }

    public function validateSource($attribute, $value)
    {
        Source::checkSource($value);
    }

    public function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

    /**
     * Amount should not be updated by via input if line items already exists
     * for the invoice.
     *
     * @param array $input
     */
    public function validateEditDraftAmount(array $input)
    {
        if (isset($input[Entity::AMOUNT]) === false)
        {
            return;
        }

        $invoice = $this->entity;

        if ($invoice->lineItems()->count() > 0)
        {
            $message = 'amount cannot be updated if ' .
                       $invoice->getTypeLabel() .  ' has line_items';

            throw new BadRequestValidationFailureException($message);
        }
    }

    /**
     * Does few validations around merchant data to decide if invoice should
     * allowed to be created or not.
     *
     * @return null
     *
     * @throws BadRequestException
     */
    public function validateMerchantSpecificData()
    {
        $invoice = $this->entity;
        $merchant = $invoice->merchant;

        $this->validateMerchantHasKeys($merchant);
        $this->validateMerchantIsNotFeeBearer($merchant, $invoice);
    }

    /**
     * Validates if merchant has API keys generated in advance before using
     * invoices.
     * This is done because hosted page (invoice payment) will not load
     * and will throw an exception if Invoice gets created without
     * merchant having API keys.
     *
     * @param Merchant\Entity $merchant
     *
     * @throws BadRequestException
     */
    protected function validateMerchantHasKeys(Merchant\Entity $merchant)
    {
        //
        // Validates if merchant has API keys generated in advance before using
        // invoices.
        // This is done because hosted page (invoice payment) will not load
        // and will throw an exception if Invoice gets created without
        // merchant having API keys.
        //

        $keys = $merchant->keys->filter(
                    function($key, $index)
                    {
                        return ($key->isExpiredOrExpiring() === false);
                    });

        if ($keys->count() === 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_API_KEY_NOT_PRESENT,
                null,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
        }
    }

    protected function validateMerchantIsNotFeeBearer(
        Merchant\Entity $merchant,
        Entity $invoice)
    {
        //
        // If merchant is a customer-fee-bearer client, for now don't allow
        // him to create invoices of type=invoice.
        //

        if (($merchant->isFeeBearerCustomer() === true) and
            ($invoice->isTypeInvoice() === true))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVOICE_FEE_BEARER_CUSTOMER,
                null,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
        }
    }

    public function validateSendNotificationRequest(string $medium)
    {
        $invoice = $this->entity;

        $op = $invoice->isOfSubscription() ? 'sendSubscriptionNotification' : 'sendNotification';

        $this->validateOperation($op);

        if (NotifyMedium::isMediumValid($medium) === false)
        {
            throw new BadRequestValidationFailureException($medium . ' is not a valid communication medium.');
        }

        if (($medium === NotifyMedium::EMAIL) and
            (empty($invoice->getCustomerEmail())))
        {
            throw new BadRequestValidationFailureException(
                'Email can not be sent since email address has not been provided.'
            );
        }

        if (($medium === NotifyMedium::SMS) and
            (empty($invoice->getCustomerContact())))
        {
            throw new BadRequestValidationFailureException(
                'SMS can not be sent since contact number has not been provided.'
            );
        }
    }

    public function validateOperation(string $operation)
    {
        $invoice = $this->entity;

        if (in_array($operation, $invoice->getValidOperations(), true) === false)
        {
            throw new LogicException(
                "Invoice validator: $operation is not a valid",
                null,
                ['id' => $invoice->getId()]);
        }

        switch ($operation)
        {
            case 'update':
            case 'cancelInvoice':
                $allowedStatuses = [
                    Status::DRAFT,
                    Status::ISSUED,
                ];

                break;

            case 'sendNotification':
            case 'expireInvoice':
                $allowedStatuses = [
                    Status::ISSUED,
                ];

                break;

            case 'sendSubscriptionNotification':
                // Right now, we don't send anything at all
                $allowedStatuses = [];

                break;

            default:
                $allowedStatuses = [
                    Status::DRAFT,
                ];
        }

        $invoiceStatus = $invoice->getStatus();

        if (in_array($invoiceStatus, $allowedStatuses, true) === false)
        {
            $message = 'Operation not allowed for ' . $invoice->getTypeLabel() .
                       ' in ' . $invoiceStatus . ' status.';

            throw new BadRequestValidationFailureException($message);
        }
    }

    /**
     * Validates if an invoice can be issued or not.
     * It has the following checks:
     *  - Gap between invoice issue and expired by should be greater that a min
     *  - Invoice should have amount set to a non-zero value
     *  - Either description (minimal invoice) or non-zero line items should exist
     */
    public function validateInvoiceIssue()
    {
        $invoice = $this->entity;

        $type = $invoice->getType();

        switch ($type)
        {
            case Type::INVOICE:
                $this->validateInvoiceIssueForInvoiceType($invoice);
                break;

            default:
                $this->validateInvoiceIssueForOtherTypes($invoice);
                break;
        }

        $this->validateInvoiceIssueExpireBy();
    }

    public function validateInvoiceIssueExpireBy()
    {
        $invoice = $this->entity;

        // If expired_by is not set at all, nothing to validate.
        if ($invoice->getExpireBy() === null)
        {
            return;
        }

        $now = Carbon::now('Asia/Kolkata');
        $minExpireBy = $now->copy()->addSeconds(self::MIN_EXPIRY_SECS);

        if ($invoice->getExpireBy() < $minExpireBy->timestamp)
        {
            $message = 'expire_by should be at least ' .
                        $minExpireBy->diffForHumans($now) . ' the time of issue.';

            throw new BadRequestValidationFailureException($message);
        }
    }

    /**
     * Invoice is only payable if it's not deleted and is in either
     * issued or partially_paid state.
     *
     * @return void
     * @throws BadRequestValidationFailureException
     */
    public function validateInvoicePayable()
    {
        $invoice = $this->entity;

        if ($invoice->trashed())
        {
            throw new BadRequestValidationFailureException(
                $invoice->getTypeLabel() . ' is not payable as it is deleted.');
        }

        $status = $invoice->getStatus();

        if (in_array($status, [Status::ISSUED, Status::PARTIALLY_PAID], true) === false)
        {
            $message = $invoice->getTypeLabel() . ' is not payable in ' . $status . ' status.';

            throw new BadRequestValidationFailureException($message);
        }
    }

    public function validateInvoiceMaxAllowedLineItems()
    {
        $invoice        = $this->entity;
        $lineItemsCount = $invoice->lineItems()->count();

        if ($lineItemsCount >= self::MAX_ALLOWED_LINE_ITEMS)
        {
            $message = 'The line items may not have more than ' .
                        self::MAX_ALLOWED_LINE_ITEMS . ' items in total.';

            throw new BadRequestValidationFailureException($message);
        }
    }

    protected function validateInvoiceIssueForInvoiceType(Entity $invoice)
    {
        $lineItemsCount = $invoice->lineItems()->count();

        if ($lineItemsCount === 0)
        {
            throw new BadRequestValidationFailureException(
                'line_items is required.');
        }

        $customer = $invoice->customer;

        if (empty($customer))
        {
            throw new BadRequestValidationFailureException(
                'customer is required.');
        }
    }

    protected function validateInvoiceIssueForOtherTypes(Entity $invoice)
    {
        $invoiceAmount = $invoice->getAmount();

        if ($invoiceAmount === null)
        {
            throw new BadRequestValidationFailureException(
                'amount cannot be empty.');
        }

        $lineItemsCount = $invoice->lineItems()->count();
        $description    = $invoice->getDescription();

        if (($lineItemsCount === 0) and ($description === null))
        {
            throw new BadRequestValidationFailureException(
                'description is required.');
        }
    }

    // protected static $createValidators = [
    //     Entity::DISCOUNT_FLAT,
    //     Entity::DISCOUNT_PERCENT,
    // ];

    // public function validateDiscountFlat($input)
    // {
    //     if (isset($input[Entity::DISCOUNT_FLAT]) === false)
    //     {
    //         return;
    //     }
    //
    //     if (isset($input[Entity::DISCOUNT_PERCENT]) === true)
    //     {
    //         throw new BadRequestValidationFailureException(
    //             'Both discount_flat and discount_percent should not be set.'
    //         );
    //     }
    //
    //     $totalAmount = $discountableAmount = $input[Entity::TOTAL_AMOUNT];
    //
    //     if (isset($input[Entity::TOTAL_TAX]) === true)
    //     {
    //         $discountableAmount = $totalAmount - $input[Entity::TOTAL_TAX];
    //     }
    //
    //     if ($input[Entity::DISCOUNT_FLAT] > $discountableAmount)
    //     {
    //         throw new BadRequestValidationFailureException(
    //             'Discount cannot be greater than the total amount of the invoice',
    //             null,
    //             [
    //                 'total_amount'  => $input[Entity::TOTAL_AMOUNT],
    //                 'discount_flat' => $input[Entity::DISCOUNT_FLAT],
    //             ]
    //         );
    //     }
    // }
    //
    // public function validateDiscountPercent($input)
    // {
    //     if (isset($input[Entity::DISCOUNT_PERCENT]) === false)
    //     {
    //         return;
    //     }
    //
    //     if (isset($input[Entity::DISCOUNT_FLAT]) === true)
    //     {
    //         throw new BadRequestValidationFailureException(
    //             'Both discount_flat and discount_percent should not be set.'
    //         );
    //     }
    // }
}
