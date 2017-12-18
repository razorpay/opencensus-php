<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Base;
use RZP\Models\Feature;
use RZP\Models\Customer;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    // We have rules on create and update for the two status: DRAFT, ISSUED.
    // Eg. In ISSUED state, you cannot update amount of the invoice. There are
    //     rules to accommodate such requirements. This way it's good to manage and
    //     is easy to understand.
    //
    // - Create invoice in DRAFT status
    // - Create invoice in ISSUED status
    // - Update invoice when it's in DRAFT status
    // - Update invoice when it's in ISSUED status

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

    protected static $createRules = [
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|epoch|nullable',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40|nullable',
        Entity::INVOICE_NUMBER      => 'sometimes|string|min:1|max:40|nullable',
        Entity::VIEW_LESS           => 'filled|in:1',
        Entity::SOURCE              => 'filled|string|max:32|custom',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS          => 'sometimes|sequential_array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'filled|boolean|custom',
        Entity::AMOUNT              => 'filled|mysql_unsigned_int|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'filled|in:INR',
        Entity::BILLING_START       => 'filled|epoch',
        Entity::BILLING_END         => 'filled|epoch',
        Entity::DRAFT               => 'filled|boolean',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable',
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
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40|nullable',
        Entity::INVOICE_NUMBER      => 'sometimes|string|min:1|max:40|nullable',
        Entity::VIEW_LESS           => 'filled|in:1',
        Entity::SOURCE              => 'filled|string|max:32|custom',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS          => 'sometimes|sequential_array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'filled|boolean|custom',
        Entity::AMOUNT              => 'filled|mysql_unsigned_int|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'filled|in:INR',
        Entity::BILLING_START       => 'filled|epoch',
        Entity::BILLING_END         => 'filled|epoch',
        Entity::DRAFT               => 'filled|boolean',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable',
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
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40|nullable',
        Entity::INVOICE_NUMBER      => 'sometimes|string|min:1|max:40|nullable',
        Entity::VIEW_LESS           => 'filled|in:1',
        Entity::SOURCE              => 'filled|string|max:32|custom',
        Entity::TYPE                => 'filled|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS          => 'sometimes|sequential_array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'filled|boolean|custom',
        Entity::AMOUNT              => 'filled|mysql_unsigned_int|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'filled|in:INR',
        Entity::BILLING_START       => 'filled|epoch',
        Entity::BILLING_END         => 'filled|epoch',
        Entity::DRAFT               => 'filled|in:0',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable',
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
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40|nullable',
        Entity::INVOICE_NUMBER      => 'sometimes|string|min:1|max:40|nullable',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS          => 'sometimes|sequential_array|min:1|max:' . self::MAX_ALLOWED_LINE_ITEMS,
        Entity::PARTIAL_PAYMENT     => 'filled|boolean|custom',
        Entity::AMOUNT              => 'filled|mysql_unsigned_int|min:100',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::BILLING_START       => 'filled|epoch',
        Entity::BILLING_END         => 'filled|epoch',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable',
        Entity::DRAFT               => 'filled|boolean',
        Entity::CALLBACK_URL        => 'sometimes|url|nullable',
        Entity::CALLBACK_METHOD     => 'required_with:callback_url|sometimes|string|in:get|nullable',
    ];

    protected static $editIssuedRules  = [
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::COMMENT             => 'sometimes|string|max:2048',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40|nullable',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable',
        Entity::PARTIAL_PAYMENT     => 'filled|boolean|custom',
        Entity::CALLBACK_URL        => 'sometimes|url|nullable',
        Entity::CALLBACK_METHOD     => 'required_with:callback_url|sometimes|string|in:get|nullable',
    ];

    /**
     * Rule used when in update request one sends customer dict to update invoice's
     * copy of customer details.
     *
     * @var array
     */
    protected static $editCustomerDetailsRules = [
        Customer\Entity::NAME               => 'sometimes|regex:(^[a-zA-Z. 0-9\']+$)|max:50|nullable',
        Customer\Entity::EMAIL              => 'sometimes|email',
        Customer\Entity::CONTACT            => 'sometimes|contact_syntax',
        Customer\Entity::BILLING_ADDRESS_ID => 'sometimes|public_id|size:19|nullable',
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

    public function validatePartialPayment($attribute, $value)
    {
        if ($value === '0')
        {
            return;
        }

        $merchant = $this->entity->merchant;

        $feature = Feature\Constants::INVOICE_PARTIAL_PAYMENTS;

        if ($merchant->isFeatureEnabled($feature) === false)
        {
            throw new BadRequestValidationFailureException(
                'Partial payment feature is not enabled',
                Entity::PARTIAL_PAYMENT);
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

        // If expired_by is not set at all, nothing to validate.
        if ($invoice->getExpireBy() === null)
        {
            return;
        }

        $now = Carbon::now(Timezone::IST);
        $minExpireBy = $now->copy()->addSeconds(self::MIN_EXPIRY_SECS);

        if ($invoice->getExpireBy() < $minExpireBy->getTimestamp())
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

    public function validateInvoiceViewable()
    {
        $invoice = $this->entity;

        $id    = $invoice->getPublicId();
        $label = $invoice->getTypeLabel();

        switch ($invoice->getStatus())
        {
            //
            // If invoice is in draft, cancelled state we don't send any data
            // but just following error message to view.
            //

            case Status::DRAFT:

                throw new BadRequestValidationFailureException("$label with id $id is not issued yet");

            case Status::CANCELLED:

                throw new BadRequestValidationFailureException("$label with id $id is cancelled");

            //
            // If invoice type is expired we still send the data and JS code
            // shows a torn page with other basic attributes. But in case of
            // other types we would throw error so the error page with proper
            // message is rendered.
            //

            case Status::EXPIRED:

                if ($invoice->isTypeInvoice() === false)
                {
                    throw new BadRequestValidationFailureException("$label with id $id is expired");
                }

                break;

            default:

                break;
        }
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

        if (($lineItemsCount === 0) and ($description === null))
        {
            throw new BadRequestValidationFailureException('description is required.');
        }
    }
}
