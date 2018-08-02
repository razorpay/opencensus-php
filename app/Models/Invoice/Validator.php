<?php

namespace RZP\Models\Invoice;

use Lib\Gstin;
use Carbon\Carbon;

use RZP\Base;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Customer;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 *
 * @package RZP\Models\Invoice
 *
 * @property $entity    Entity
 */
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
    const ISSUE_BATCH   = 'issueBatch';

    const MAX_ALLOWED_LINE_ITEMS = 20;

    /**
     * A minimum of 15 minutes of gap must exist between invoice
     * issue and expired by timestamps.
     */
    const MIN_EXPIRY_SECS = 900;

    /**
     * With this constant there is validation rule for
     * "notify invoices of batch" request.
     */
    const NOTIFY_INVOICES_OF_BATCH = 'notify_invoices_of_batch';

    protected static $createRules = [
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|epoch|nullable',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40|nullable|custom',
        Entity::INVOICE_NUMBER      => 'sometimes|string|min:1|max:40|nullable',
        Entity::VIEW_LESS           => 'filled|in:1',
        Entity::SOURCE              => 'filled|string|max:32|custom',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS          => 'sometimes|sequential_array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'filled|boolean',
        Entity::AMOUNT              => 'filled|mysql_unsigned_int|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'filled|in:INR',
        Entity::BILLING_START       => 'filled|epoch',
        Entity::BILLING_END         => 'filled|epoch',
        Entity::DRAFT               => 'filled|boolean',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable',
        Entity::SUPPLY_STATE_CODE   => 'filled|string|custom',
        Entity::CALLBACK_URL        => 'filled|url',
        Entity::CALLBACK_METHOD     => 'required_with:callback_url|filled|string|in:get',
    ];

    //
    // Following is redundant and same as $createRules but keeping it as it keeps code
    // at other places clean
    //

    protected static $createDraftRules = [
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|epoch|nullable',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40|nullable|custom',
        Entity::INVOICE_NUMBER      => 'sometimes|string|min:1|max:40|nullable',
        Entity::VIEW_LESS           => 'filled|in:1',
        Entity::SOURCE              => 'filled|string|max:32|custom',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS          => 'sometimes|sequential_array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'filled|boolean',
        Entity::AMOUNT              => 'filled|mysql_unsigned_int|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'filled|in:INR',
        Entity::BILLING_START       => 'filled|epoch',
        Entity::BILLING_END         => 'filled|epoch',
        Entity::DRAFT               => 'filled|boolean',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable',
        Entity::SUPPLY_STATE_CODE   => 'filled|string|custom',
        Entity::CALLBACK_URL        => 'filled|url',
        Entity::CALLBACK_METHOD     => 'required_with:callback_url|filled|string|in:get',
    ];

    protected static $createIssuedRules = [
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|epoch|nullable',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40|nullable|custom',
        Entity::INVOICE_NUMBER      => 'sometimes|string|min:1|max:40|nullable',
        Entity::VIEW_LESS           => 'filled|in:1',
        Entity::SOURCE              => 'filled|string|max:32|custom',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS          => 'sometimes|sequential_array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'filled|boolean',
        Entity::AMOUNT              => 'filled|mysql_unsigned_int|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'filled|in:INR',
        Entity::BILLING_START       => 'filled|epoch',
        Entity::BILLING_END         => 'filled|epoch',
        Entity::DRAFT               => 'filled|in:0',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable',
        Entity::SUPPLY_STATE_CODE   => 'filled|string|custom',
        Entity::CALLBACK_URL        => 'filled|url',
        Entity::CALLBACK_METHOD     => 'required_with:callback_url|filled|string|in:get',
    ];

    protected static $editDraftRules  = [
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|epoch|nullable',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40|nullable|custom',
        Entity::INVOICE_NUMBER      => 'sometimes|string|min:1|max:40|nullable',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS          => 'sometimes|sequential_array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'filled|boolean',
        Entity::AMOUNT              => 'filled|mysql_unsigned_int|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::BILLING_START       => 'filled|epoch',
        Entity::BILLING_END         => 'filled|epoch',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable',
        Entity::DRAFT               => 'filled|boolean',
        Entity::SUPPLY_STATE_CODE   => 'sometimes|nullable|custom',
        Entity::CALLBACK_URL        => 'sometimes|url|nullable',
        Entity::CALLBACK_METHOD     => 'required_with:callback_url|sometimes|string|in:get|nullable',
    ];

    protected static $editIssuedRules  = [
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40|nullable|custom',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable',
        Entity::PARTIAL_PAYMENT     => 'filled|boolean',
        Entity::CALLBACK_URL        => 'sometimes|url|nullable',
        Entity::CALLBACK_METHOD     => 'required_with:callback_url|sometimes|string|in:get|nullable',
    ];

    protected static $editPaidRules = [
        Entity::NOTES               => 'sometimes|notes',
    ];

    protected static $editPartiallyPaidRules = [
        Entity::NOTES               => 'sometimes|notes',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable|custom',
    ];

    protected static $editExpiredRules = [
        Entity::NOTES               => 'sometimes|notes',
    ];

    protected static $editCancelledRules = [
        Entity::NOTES               => 'sometimes|notes',
    ];

    protected static $notifyInvoicesOfBatchRules = [
        Entity::SMS_NOTIFY          => 'required|boolean',
        Entity::EMAIL_NOTIFY        => 'required|boolean',
    ];

    /**
     * Rule used when in update request one sends customer dict to update invoice's
     * copy of customer details.
     *
     * @var array
     */
    protected static $editCustomerDetailsRules = [
        Customer\Entity::NAME                => 'sometimes|regex:(^[a-zA-Z. 0-9\']+$)|max:50|nullable',
        Customer\Entity::EMAIL               => 'sometimes|nullable|email',
        Customer\Entity::CONTACT             => 'sometimes|nullable|contact_syntax',
        Customer\Entity::GSTIN               => 'sometimes|nullable|gstin',
        Customer\Entity::BILLING_ADDRESS_ID  => 'sometimes|public_id|size:19|nullable',
        Customer\Entity::SHIPPING_ADDRESS_ID => 'sometimes|public_id|size:19|nullable',
    ];

    protected static $issueBatchRules = [
        Entity::IDS                 => 'sometimes|array|min:1|max:100',
        Entity::IDS . '.*'          => 'required|public_id|size:18',
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
    ];

    protected static $invoiceStatsByBatchesRules = [
        Entity::BATCH_IDS           => 'required|array|min:1|max:100',
        Entity::BATCH_IDS . '.*'    => 'required|public_id|size:20',
    ];

    //
    // Custom validators.
    //

    protected static $createValidators =[
        Entity::AMOUNT,
        Entity::CUSTOMER_ID,
    ];

    protected static $editDraftValidators = [
        Entity::AMOUNT,
        Entity::CUSTOMER_ID,
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
     * Validates that only one of customer_id or customer key is sent
     * in request input.
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateCustomerId(array $input)
    {
        if ((array_key_exists(Entity::CUSTOMER_ID, $input) === true) and
            (array_key_exists(Entity::CUSTOMER, $input) === true))
        {
            throw new BadRequestValidationFailureException(
                'Either of customer_id or customer must be sent in input');
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
        $entity = $this->entity;

        $type = $input[Entity::TYPE] ?? $this->entity->getType();

        // In case entity's type is not set, default to be assumed is 'invoice'
        $type = $type ?: Type::INVOICE;

        if ($type === Type::INVOICE)
        {
            throw new BadRequestValidationFailureException(
                'amount can be only sent for ecod or link types.');
        }

        //
        // For non-invoice type, line items can be sent but need to ensure
        // only one is being used, either amount or line_items.
        //
        if (isset($input[Entity::LINE_ITEMS]) === true)
        {
            throw new BadRequestValidationFailureException(
                'amount should not be sent if line_items are being sent in the input.');
        }

        if ($entity->lineItems()->count() > 0)
        {
            $label = $entity->getTypeLabel();

            throw new BadRequestValidationFailureException(
                "amount cannot be updated if $label has line_items");
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

    public function validateSource($attribute, $value)
    {
        Source::checkSource($value);
    }

    public function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

    public function validateSupplyStateCode($attribute, $value)
    {
        if (Gstin::isValidStateCode($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Supply state code is not valid',
                Entity::SUPPLY_STATE_CODE,
                [Entity::SUPPLY_STATE_CODE => $value]);
        }
    }

    public function validateExpireBy(string $attribute, int $expireBy)
    {
        $now = Carbon::now(Timezone::IST);

        $minExpireBy = $now->copy()->addSeconds(self::MIN_EXPIRY_SECS);

        if ($expireBy < $minExpireBy->getTimestamp())
        {
            $message = 'expire_by should be at least ' . $minExpireBy->diffForHumans($now) . ' current time';

            throw new BadRequestValidationFailureException($message);
        }
    }

    /**
     * For non empty receipt, validates that it's unique for given merchant across it's NON cancelled & expired items
     * @param  string $attribute
     * @param  string $receipt
     * @throws BadRequestValidationFailureException
     */
    public function validateReceipt(string $attribute, string $receipt)
    {
        if (empty($receipt) === false)
        {
            $isDuplicateReceipt = app('repo')->invoice->isDuplicateReceipt($this->entity, $receipt);

            if ($isDuplicateReceipt === true)
            {
                throw new BadRequestValidationFailureException("receipt must be unique for each item : {$receipt}");
            }
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
        $invoice  = $this->entity;
        $merchant = $invoice->merchant;

        $this->validateMerchantIsNotFeeBearer($merchant, $invoice);
    }

    protected function validateMerchantIsNotFeeBearer(Merchant\Entity $merchant, Entity $invoice)
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
            throw new BadRequestValidationFailureException(
                $medium . ' is not a valid communication medium.');
        }

        if (($medium === NotifyMedium::EMAIL) and
            (empty($invoice->getCustomerEmail())))
        {
            throw new BadRequestValidationFailureException(
                'Email can not be sent since email address has not been provided.');
        }

        if (($medium === NotifyMedium::SMS) and
            (empty($invoice->getCustomerContact())))
        {
            throw new BadRequestValidationFailureException(
                'SMS can not be sent since contact number has not been provided.');
        }
    }

    /**
     * Validates if given operation is allowed against invoice's current
     * status. $operations is generally the names of core's methods.
     *
     * @param string $operation
     *
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     */
    public function validateOperation(string $operation)
    {
        $invoice = $this->entity;

        if (in_array($operation, $invoice->getValidOperations(), true) === false)
        {
            throw new LogicException(
                "Invoice validator: $operation is not valid operation",
                null,
                ['id' => $invoice->getId()]);
        }

        switch ($operation)
        {
            case 'update':
                $allowedStatuses = [
                    Status::DRAFT,
                    Status::ISSUED,
                    Status::PAID,
                    Status::PARTIALLY_PAID,
                    Status::EXPIRED,
                    Status::CANCELLED,
                ];

                break;

            case 'cancelInvoice':
                $allowedStatuses = [
                    Status::DRAFT,
                    Status::ISSUED,
                ];

                break;

            case 'sendNotification':
                $allowedStatuses = [
                    Status::ISSUED,
                    Status::PARTIALLY_PAID,
                ];

                break;

            case 'notifyInvoiceIssued':
            case 'expireInvoice':
                $allowedStatuses = [
                    Status::ISSUED,
                ];

                break;

            case 'sendSubscriptionNotification':
                // Right now, we don't send anything at all
                $allowedStatuses = [];

                break;

            case 'notifyInvoiceExpired':

                $allowedStatuses = [
                    Status::EXPIRED,
                ];

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

        if ($invoice->getExpireBy() !== null)
        {
            $this->validateExpireBy(Entity::EXPIRE_BY, $invoice->getExpireBy());
        }
    }

    /**
     * Invoice is only payable if it's not deleted and is in either
     * issued or partially_paid state.
     *
     * @param Payment\Entity $payment
     * @return void
     * @throws BadRequestValidationFailureException
     */
    public function validateInvoicePayable(Payment\Entity $payment)
    {
        $invoice = $this->entity;

        $isPartialPayment = ($invoice->getAmount() !== $payment->getAmount());
        $dimensions = $invoice->getMetricDimensions(['is_partial_payment' => (int) $isPartialPayment]);
        $this->getTrace()->count(Metric::INVOICE_PAYMENT_ATTEMPTS_TOTAL, $dimensions);

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

    public function validateInvoiceViewable()
    {
        $invoice = $this->entity;
        $id      = $invoice->getPublicId();
        $label   = $invoice->getTypeLabel();

        //
        // We have two views - invoice.js and api's blade for invoices & payment links respectively. Unfortunately,
        // in cases of non-issued status invoice they expect either an exception(rendered as standard minimal error
        // view) or full entity(rendered as designed torn page with partial entity details) and hence following logic.
        //

        // Draft: All views expect exception
        if ($invoice->isDraft() === true)
        {
            throw new BadRequestValidationFailureException("$label with id $id is not issued yet");
        }
        // Canceled: Link view shows torn page with details & invoice view expects exception
        else if (($invoice->isCancelled() === true) and ($invoice->isTypeInvoice() === true))
        {
            throw new BadRequestValidationFailureException("$label with id $id is cancelled");
        }
        // Expired: All views show custom torn or some kind of page and need data
    }

    public function validateMaxAllowedLineItems()
    {
        $invoice = $this->entity;
        $count   = $invoice->lineItems()->count();

        if ($count >= self::MAX_ALLOWED_LINE_ITEMS)
        {
            $message = 'The invoice may not have more than ' . self::MAX_ALLOWED_LINE_ITEMS . ' items in total.';

            throw new BadRequestValidationFailureException(
                        $message,
                        Entity::LINE_ITEMS,
                        [
                            Entity::ID                => $invoice->getId(),
                            'max_allowed_line_items'  => self::MAX_ALLOWED_LINE_ITEMS,
                            'actual_line_items_count' => $count,
                        ]);
        }
    }

    public function validateNotifyInvoicesOfBatch(
        Settings\Accessor $settingsAccessor,
        Batch\Entity $batch,
        array $input)
    {
        // 1. Validates the input
        $this->validateInput(Validator::NOTIFY_INVOICES_OF_BATCH, $input);

        // 2. Validates that batch notification request was already sent or not
        $smsNotified      = $settingsAccessor->get(Entity::SMS_NOTIFY);
        $emailNotified    = $settingsAccessor->get(Entity::EMAIL_NOTIFY);

        if (($smsNotified === true) or ($emailNotified === true))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_NOTIFICATIONS_SENT_ALREADY,
                Entity::BATCH_ID,
                [
                    Entity::BATCH_ID => $batch->getId(),
                    'input'          => $input,
                ]);
        }
    }

    protected function validateInvoiceIssueForInvoiceType(Entity $invoice)
    {
        $lineItemsCount = $invoice->lineItems()->count();

        if ($lineItemsCount === 0)
        {
            throw new BadRequestValidationFailureException('line_items is required.');
        }

        $customer = $invoice->customer;

        if ((empty($customer) === true) and
            ($invoice->isOfSubscription() === false))
        {
            throw new BadRequestValidationFailureException('customer is required.');
        }
    }

    protected function validateInvoiceIssueForOtherTypes(Entity $invoice)
    {
        $invoiceAmount = $invoice->getAmount();

        if ($invoiceAmount === null)
        {
            throw new BadRequestValidationFailureException('amount cannot be empty.');
        }

        $lineItemsCount = $invoice->lineItems()->count();
        $description    = $invoice->getDescription();

        // For description need to do blank() check as it is 'sometimes' in Validator.
        if (($lineItemsCount === 0) and (blank($description) === true))
        {
            throw new BadRequestValidationFailureException('description is required.');
        }
    }
}
