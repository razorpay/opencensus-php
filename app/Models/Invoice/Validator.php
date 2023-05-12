<?php

namespace RZP\Models\Invoice;

use Lib\Gstin;
use Carbon\Carbon;

use App;
use RZP\Base;
use RZP\Error\Error;
use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Customer;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Models\Currency\Currency;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Exception\ExtraFieldsException;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Feature\Constants as Features;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\SubscriptionRegistration\SubscriptionRegistrationConstants;

/**
 * Class Validator
 *
 * @package RZP\Models\Invoice
 *
 * @property Entity $entity
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

    const RECEIPT_REQUIRED = 'receipt_required';

    /**
     * Caps for maximum allowed line items.
     * @see Merchant\RazorxTreatment::INV_INCREASED_LINE_ITEMS_CAP.
     */
    const MAX_ALLOWED_LINE_ITEMS = 20;
    const MAX_ALLOWED_LINE_ITEMS_EXPERIMENTAL = 50;

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

    /**
     * Rate limit on items sending for bulk invoice create.
     */
    const MAX_BULK_INVOICES_LIMIT = 15;

    const RECEIPT_MUTEX_TIMEOUT  = 5; // 5 seconds timeout

    protected static $createRules = [
        Entity::SMS_NOTIFY               => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY             => 'sometimes|boolean',
        Entity::DATE                     => 'sometimes|epoch|nullable',
        Entity::TERMS                    => 'sometimes|string|max:2048',
        Entity::NOTES                    => 'sometimes|notes',
        Entity::COMMENT                  => 'sometimes|string|max:2048|utf8',
        Entity::RECEIPT                  => 'sometimes|string|min:1|max:40|nullable|custom',
        Entity::INTERNAL_REF             => 'filled|string|min:1|max:64',
        Entity::INVOICE_NUMBER           => 'sometimes|string|min:1|max:40|nullable',
        Entity::VIEW_LESS                => 'filled|in:1',
        Entity::SOURCE                   => 'filled|string|max:32|custom',
        Entity::TYPE                     => 'filled|string|max:16|custom',
        Entity::CUSTOMER                 => 'sometimes|array',
        Entity::CUSTOMER_ID              => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS               => 'sometimes|sequential_array|min:1',
        Entity::PARTIAL_PAYMENT          => 'filled|boolean',
        Entity::FIRST_PAYMENT_MIN_AMOUNT => 'sometimes|mysql_unsigned_int|nullable|min_amount',
        Entity::AMOUNT                   => 'filled|integer',
        Entity::DESCRIPTION              => 'sometimes|string|max:2048',
        Entity::CURRENCY                 => 'filled|currency|custom',
        Entity::BILLING_START            => 'filled|epoch',
        Entity::BILLING_END              => 'filled|epoch',
        Entity::DRAFT                    => 'filled|boolean',
        Entity::EXPIRE_BY                => 'sometimes|epoch|nullable',
        Entity::BIG_EXPIRE_BY            => 'sometimes|epoch|nullable',
        Entity::SUPPLY_STATE_CODE        => 'filled|string|custom',
        Entity::CALLBACK_URL             => 'filled|url',
        Entity::CALLBACK_METHOD          => 'required_with:callback_url|filled|string|in:get',
        Entity::IDEMPOTENCY_KEY          => 'sometimes|string',
        Entity::OPTIONS_KEY              => 'sometimes|array',
        Entity::REMINDER_ENABLE          => 'sometimes|boolean',
        Entity::OFFER_AMOUNT             => 'sometimes|integer',
        Entity::REF_NUM                  => 'sometimes|string',
    ];

    //
    // Following is redundant and same as $createRules but keeping it as it keeps code
    // at other places clean
    //

    protected static $createDraftRules = [
        Entity::SMS_NOTIFY               => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY             => 'sometimes|boolean',
        Entity::DATE                     => 'sometimes|epoch|nullable',
        Entity::TERMS                    => 'sometimes|string|max:2048',
        Entity::NOTES                    => 'sometimes|notes',
        Entity::COMMENT                  => 'sometimes|string|max:2048|utf8',
        Entity::RECEIPT                  => 'sometimes|string|min:1|max:40|nullable|custom',
        Entity::INVOICE_NUMBER           => 'sometimes|string|min:1|max:40|nullable',
        Entity::VIEW_LESS                => 'filled|in:1',
        Entity::SOURCE                   => 'filled|string|max:32|custom',
        Entity::TYPE                     => 'filled|string|max:16|custom',
        Entity::CUSTOMER                 => 'sometimes|array',
        Entity::CUSTOMER_ID              => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS               => 'sometimes|sequential_array|min:1',
        Entity::PARTIAL_PAYMENT          => 'filled|boolean',
        Entity::FIRST_PAYMENT_MIN_AMOUNT => 'sometimes|mysql_unsigned_int|nullable|min_amount',
        Entity::AMOUNT                   => 'filled|integer|min_amount',
        Entity::DESCRIPTION              => 'sometimes|string|max:2048',
        Entity::CURRENCY                 => 'filled|currency|custom',
        Entity::BILLING_START            => 'filled|epoch',
        Entity::BILLING_END              => 'filled|epoch',
        Entity::DRAFT                    => 'filled|boolean',
        Entity::EXPIRE_BY                => 'sometimes|epoch|nullable',
        Entity::BIG_EXPIRE_BY            => 'sometimes|epoch|nullable',
        Entity::SUPPLY_STATE_CODE        => 'filled|string|custom',
        Entity::CALLBACK_URL             => 'filled|url',
        Entity::CALLBACK_METHOD          => 'required_with:callback_url|filled|string|in:get',
        Entity::OFFER_AMOUNT             => 'sometimes|integer',
    ];

    protected static $createIssuedRules = [
        Entity::SMS_NOTIFY               => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY             => 'sometimes|boolean',
        Entity::DATE                     => 'sometimes|epoch|nullable',
        Entity::TERMS                    => 'sometimes|string|max:2048',
        Entity::NOTES                    => 'sometimes|notes',
        Entity::COMMENT                  => 'sometimes|string|max:2048|utf8',
        Entity::RECEIPT                  => 'sometimes|string|min:1|max:40|nullable|custom',
        Entity::INTERNAL_REF             => 'filled|string|min:1|max:64',
        Entity::INVOICE_NUMBER           => 'sometimes|string|min:1|max:40|nullable',
        Entity::VIEW_LESS                => 'filled|in:1',
        Entity::SOURCE                   => 'filled|string|max:32|custom',
        Entity::TYPE                     => 'filled|string|max:16|custom',
        Entity::CUSTOMER                 => 'sometimes|array',
        Entity::CUSTOMER_ID              => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS               => 'sometimes|sequential_array|min:1',
        Entity::PARTIAL_PAYMENT          => 'filled|boolean',
        Entity::FIRST_PAYMENT_MIN_AMOUNT => 'sometimes|mysql_unsigned_int|nullable|min_amount',
        Entity::AMOUNT                   => 'filled|integer',
        Entity::DESCRIPTION              => 'sometimes|string|max:2048',
        Entity::CURRENCY                 => 'filled|currency|custom',
        Entity::BILLING_START            => 'filled|epoch',
        Entity::BILLING_END              => 'filled|epoch',
        Entity::DRAFT                    => 'filled|in:0',
        Entity::EXPIRE_BY                => 'sometimes|epoch|nullable',
        Entity::BIG_EXPIRE_BY            => 'sometimes|epoch|nullable',
        Entity::SUPPLY_STATE_CODE        => 'filled|string|custom',
        Entity::CALLBACK_URL             => 'filled|url',
        Entity::CALLBACK_METHOD          => 'required_with:callback_url|filled|string|in:get',
        Entity::IDEMPOTENCY_KEY          => 'sometimes|string',
        Entity::OPTIONS_KEY              => 'sometimes|array',
        Entity::REMINDER_ENABLE          => 'sometimes|bool',
        Entity::OFFER_AMOUNT             => 'sometimes|integer',
        Entity::REF_NUM                  => 'sometimes|string',
    ];

    protected static $editDraftRules = [
        Entity::SMS_NOTIFY               => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY             => 'sometimes|boolean',
        Entity::DATE                     => 'sometimes|epoch|nullable',
        Entity::TERMS                    => 'sometimes|string|max:2048',
        Entity::NOTES                    => 'sometimes|notes',
        Entity::COMMENT                  => 'sometimes|string|max:2048|utf8',
        Entity::RECEIPT                  => 'sometimes|string|min:1|max:40|nullable|custom',
        Entity::INVOICE_NUMBER           => 'sometimes|string|min:1|max:40|nullable',
        Entity::CUSTOMER                 => 'sometimes|array',
        Entity::CUSTOMER_ID              => 'sometimes|public_id|size:19|nullable',
        Entity::LINE_ITEMS               => 'sometimes|sequential_array|min:1|custom',
        Entity::PARTIAL_PAYMENT          => 'filled|boolean',
        Entity::FIRST_PAYMENT_MIN_AMOUNT => 'sometimes|mysql_unsigned_int|nullable|min_amount',
        Entity::AMOUNT                   => 'filled|integer',
        Entity::DESCRIPTION              => 'sometimes|string|max:2048',
        Entity::BILLING_START            => 'filled|epoch',
        Entity::BILLING_END              => 'filled|epoch',
        Entity::EXPIRE_BY                => 'sometimes|epoch|nullable|custom',
        Entity::BIG_EXPIRE_BY            => 'sometimes|epoch|nullable|custom',
        Entity::DRAFT                    => 'filled|boolean',
        Entity::SUPPLY_STATE_CODE        => 'sometimes|nullable|custom',
        Entity::CALLBACK_URL             => 'sometimes|url|nullable',
        Entity::CALLBACK_METHOD          => 'required_with:callback_url|sometimes|string|in:get|nullable',
    ];

    protected static $editIssuedRules = [
        Entity::TERMS                    => 'sometimes|string|max:2048',
        Entity::NOTES                    => 'sometimes|notes',
        Entity::COMMENT                  => 'sometimes|string|max:2048|utf8',
        Entity::RECEIPT                  => 'sometimes|string|min:1|max:40|nullable|custom',
        Entity::EXPIRE_BY                => 'sometimes|epoch|nullable|custom',
        Entity::BIG_EXPIRE_BY            => 'sometimes|epoch|nullable|custom',
        Entity::REMINDER_ENABLE          => 'sometimes|boolean',
        Entity::PARTIAL_PAYMENT          => 'filled|boolean',
        Entity::FIRST_PAYMENT_MIN_AMOUNT => 'sometimes|mysql_unsigned_int|nullable|min_amount',
        Entity::CALLBACK_URL             => 'sometimes|url|nullable',
        Entity::CALLBACK_METHOD          => 'required_with:callback_url|sometimes|string|in:get|nullable',
    ];

    protected static $editBillingPeriodRules = [
        Entity::BILLING_START            => 'filled|epoch',
        Entity::BILLING_END              => 'filled|epoch',
    ];

    protected static $editPaidRules = [
        Entity::NOTES               => 'sometimes|notes',
    ];

    protected static $editPartiallyPaidRules = [
        Entity::NOTES               => 'sometimes|notes',
        Entity::EXPIRE_BY           => 'sometimes|epoch|nullable|custom',
        Entity::BIG_EXPIRE_BY       => 'sometimes|epoch|nullable|custom',
        Entity::REMINDER_ENABLE     => 'sometimes|boolean',
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

    protected static $minAmountCheckRules = [
        Entity::AMOUNT => 'required|integer|min_amount'
    ];

    protected static $paymentLinkServiceSendEmailRules = [
        E::INVOICE          => 'required|array',
        E::PAYMENT          => 'sometimes|array',
        'to'                => 'required|email',
        'view'              => 'required|string|custom',
        'subject'           => 'required|string',
        'intent_url'        => 'sometimes|string',
    ];

    protected static $paymentLinkSwitchVersionRules = [
        Entity::SWITCH_TO => 'required|string|in:v1,v2',
    ];

    //
    // Custom validators.
    //

    protected static $createValidators = [
        Entity::AMOUNT,
        Entity::CUSTOMER_ID,
        Entity::FIRST_PAYMENT_MIN_AMOUNT,
        self::RECEIPT_REQUIRED,
        Features::INVOICE_EXPIRE_BY_REQD,
    ];

    protected static $editDraftValidators = [
        Entity::AMOUNT,
        Entity::CUSTOMER_ID,
        Entity::FIRST_PAYMENT_MIN_AMOUNT,
        self::RECEIPT_REQUIRED,
        Features::INVOICE_EXPIRE_BY_REQD,
    ];

    protected static $editIssuedValidators = [
        Entity::FIRST_PAYMENT_MIN_AMOUNT,
        self::RECEIPT_REQUIRED,
        Features::INVOICE_EXPIRE_BY_REQD,
    ];

    protected static $validExternalEntities = [
        E::SUBSCRIPTION_REGISTRATION,
        E::PAYMENT_PAGE,
        E::PAYMENT,
    ];

    public function validateView($attribute, $value)
    {
        if (view()->exists($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'View not found',
                'view',
                ['view' => $value,]
            );
        }
    }

    public function validateAmount(array $input)
    {
        $invoice = $this->entity;

        if (isset($input[Entity::AMOUNT]) === false)
        {
            return;
        }

        // for non-SubscriptionRegistration and not EMANDATE and not NACH, zero amount check is not required
        if (($invoice->isTypeOfSubscriptionRegistration() === true)
            and (($invoice->entity->getMethod() == SubscriptionRegistration\Method::EMANDATE) or
                ($invoice->entity->getMethod() == SubscriptionRegistration\Method::NACH)))
        {
            $amount = (int) $input[Entity::AMOUNT];

            // if amount is 0 return as no further validation required
            if ($amount === 0)
            {
                return;
            }

            // as amount is greater than 0, check if it's allowed for bank, auth type & method combination
            // If not allowed then throw error, else do further validations
            if ($this->eMandateNonZeroAllowed($invoice->tokenRegistration, $invoice->entity->getMethod()) === false)
            {
                throw new BadRequestValidationFailureException(
                    'The amount should be 0.',
                    'amount',
                    [
                        'id'                 => $invoice->getId(),
                        'amount'             => $input[Entity::AMOUNT],
                    ]);
            }
        }

        // further validations: that means amount is > 0 and allowed, do further validations

        $this->checkIfAmountIsExpectedInInput($input);

        /**
         * Removed max amount check from here since order already does the validations properly
         */
        $this->validateMinAmount($input);
    }

    /**
    * Check if merchant is allowed to charge some amount while registering for eMandate
    */
    private function eMandateNonZeroAllowed(SubscriptionRegistration\Entity $tokenRegistration, $method): bool
    {
        // if not emandate, non-zero not allowed
        if (($method !== SubscriptionRegistration\Method::EMANDATE) or
            (in_array($tokenRegistration->getAuthType(),
             SubscriptionRegistrationConstants::authTypeForDebitOnMandateRegister) === false))
        {
            return false;
        }

        // Dont allow if some other bank is selected
        if ($tokenRegistration !== null and $tokenRegistration->bankAccount !== null)
        {
            $forBank = $tokenRegistration->bankAccount->toArray()["bank_name"];

            if (($forBank !== '') and
                (in_array($forBank, SubscriptionRegistrationConstants::banksForDebitOnMandateRegister) === false))
            {
                return false;
            }
        }

        return true;
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
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateInvoiceExpireByReqd(array $input)
    {
        $type = $input[Entity::TYPE] ?? $this->entity->getType();

        $expireBy = $input[Entity::EXPIRE_BY] ?? $this->entity->getExpireBy();

        if ((empty($type) === false) and (Type::isPaymentLinkType($type) === true))
        {
            $expiryRequiredFeature = $this->entity->merchant->isFeatureEnabled(Features::INVOICE_EXPIRE_BY_REQD);

            if (($expiryRequiredFeature === true) and (empty($expireBy) === true))
            {
                throw new BadRequestValidationFailureException('expire_by is required.');
            }
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

    public function validateMinAmount(array $input)
    {
        $invoice = $this->entity;

        $amount = $input[Entity::AMOUNT];

        $inputAmount = [
            Entity::AMOUNT => $amount,
        ];

        // In edit sometimes currency will not be available when amount is edited but we need to validate amount based
        // on the existing currency.
        $currency = $input[Entity::CURRENCY] ?? $invoice->getCurrency();

        if (empty($currency) === false)
        {
            $inputAmount[Entity::CURRENCY] = $currency;
        }

        $this->validateInputValues('min_amount_check', $inputAmount);

        // Amount should always be greater than then first_payment_min_amount
        $firstPaymentMinAmount = array_key_exists(Entity::FIRST_PAYMENT_MIN_AMOUNT, $input) ?
            $input[Entity::FIRST_PAYMENT_MIN_AMOUNT] : $this->entity->getFirstPaymentMinAmount();

        if ($amount <= $firstPaymentMinAmount)
        {
            throw new BadRequestValidationFailureException(
                'The amount should be greater than the first payment min amount ',
                Entity::AMOUNT,
                [
                    'id'                       => $invoice->getId(),
                    'amount'                   => $amount,
                    'first_payment_min_amount' => $firstPaymentMinAmount,
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
     *
     * @param  string $attribute
     * @param  string $receipt
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateReceipt(string $attribute, string $receipt)
    {
        if (empty($receipt) === false)
        {
            $skipUniquenessCheck = $this->entity
                                        ->merchant
                                        ->isFeatureEnabled(Features::INVOICE_NO_RECEIPT_UNIQUE);

            if($this->entity->isPaymentPageInvoice() === true or $this->entity->isTypeOPGSPInvoice() === true)
            {
                $skipUniquenessCheck = true;
            }

            if ($skipUniquenessCheck === true)
            {
                return;
            }

            $invoice  = $this->entity;

            $merchant = $invoice->merchant;

            $isDuplicateReceipt = app('repo')->invoice->isDuplicateReceipt($this->entity, $receipt);

            if ($isDuplicateReceipt === true)
            {
                throw new BadRequestValidationFailureException(
                    "receipt must be unique for each item : {$receipt}");
            }
        }
    }

    public function validateFirstPaymentMinAmount(array $input)
    {
        if (isset($input[Entity::FIRST_PAYMENT_MIN_AMOUNT]) === false)
        {
            return;
        }

        $minAmountAllowed = $this->entity
                                 ->merchant
                                 ->isFeatureEnabled(Features::PL_FIRST_MIN_AMOUNT);

        if ($minAmountAllowed === false)
        {
            throw new ExtraFieldsException(Entity::FIRST_PAYMENT_MIN_AMOUNT);
        }

        // 1. Allow `first_payment_min_amount` to be set only for ecod and link types
        $type = $input[Entity::TYPE] ?? $this->entity->getType();

        if (Type::isPaymentLinkType($type) === false)
        {
            throw new BadRequestValidationFailureException(
                'First payment min amount can be only sent for ecod or link types.');
        }

        // 2. Do not allow `first_payment_min_amount` if partial payment is not enabled
        $partialPaymentEnabled = array_key_exists(Entity::PARTIAL_PAYMENT, $input) ?
                                    $input[Entity::PARTIAL_PAYMENT] : $this->entity->isPartialPaymentAllowed();

        $partialPaymentEnabled = (bool) $partialPaymentEnabled;

        if ($partialPaymentEnabled === false)
        {
            throw new BadRequestValidationFailureException(
                'First payment min amount cannot be set when partial payment is disabled',
                Entity::FIRST_PAYMENT_MIN_AMOUNT);
        }

        // 3. `first_payment_min_amount` should be lesser than the amount`
        $amount = array_key_exists(Entity::AMOUNT, $input) ? $input[Entity::AMOUNT] : $this->entity->getAmount();

        $firstPaymentAmount = $input[Entity::FIRST_PAYMENT_MIN_AMOUNT];

        if ($firstPaymentAmount >= $amount)
        {
            throw new BadRequestValidationFailureException(
                'First payment min amount must be lesser than the amount',
                Entity::FIRST_PAYMENT_MIN_AMOUNT,
                [
                    'amount'                   => $amount,
                    'first_payment_min_amount' => $firstPaymentAmount,
                ]);
        }
    }

    public function validateReceiptRequired(array $input)
    {

        if(empty($input["type"]) === false and
            (in_array($input["type"], Type::getDCCEInvoiceTypes(), true) === true)) {
            return;
        }

        if (empty($input[Entity::RECEIPT]) === true)
        {
            $isReceiptMandatory = $this->entity
                                       ->merchant
                                       ->isFeatureEnabled(Features::INVOICE_RECEIPT_MANDATORY);

            if ($isReceiptMandatory === true)
            {
                throw new BadRequestValidationFailureException(
                    'Receipt is a required field and must be set',
                    Entity::RECEIPT);
            }
        }
    }

    /**
     * Does few validations around merchant data to decide if invoice should
     * allowed to be created or not.
     *
     * @return null
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
        // If merchant is a customer-fee-bearer or dynamic-fee-bearer client, for now don't allow
        // him to create invoices of type=invoice.
        //


        /*
         * Not validating fee bearer for invoices generated by payment pages and subscriptions.
         * Best guess reason - no tax involved
         */
        if ($invoice->isPaymentPageInvoice() === true)
        {
            return;
        }

        if ($invoice->hasSubscription() === true)
        {
            return;
        }

        if (($merchant->isFeeBearerCustomerOrDynamic() === true) and
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

        $op = $invoice->isPaymentPageInvoice() ? 'sendPPReceiptNotification' : $op;

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

            case 'deleteInvoice':

                $allowedStatuses = [
                    Status::CANCELLED,
                    Status::EXPIRED,
                ];

                break;

            case 'sendPPReceiptNotification':

                $allowedStatuses = [
                    Status::PAID,
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
     *  - Gap between invoice issue and expired by should be greater that 15 minutes
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
            case Type::DCC_CRN:
            case Type::DCC_INV:
                $this->validateInvoiceIssueForDCCEInvoiceType($invoice);
                break;
            case Type::OPGSP_INVOICE:
            case Type::OPGSP_AWB:
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
     * Validates if a invoice is payable against given payment request.
     *
     * @param  Payment\Entity $payment
     * @return null
     */
    public function validateInvoicePayableForPayment(Payment\Entity $payment)
    {
        // Counts total payment attempts
        $invoice          = $this->entity;
        $isPartialPayment = ($invoice->getAmount() !== $payment->getAmount());
        $dimensions       = $invoice->getMetricDimensions(['is_partial_payment' => (int) $isPartialPayment, 'merchant_country_code' => (string) $invoice->merchant->getCountry()]);
        $this->getTrace()->count(Metric::INVOICE_PAYMENT_ATTEMPTS_TOTAL, $dimensions);

        $this->validateInvoicePayable();
    }

    /**
     * Invoice is only payable if it's not deleted and is in either issued or partially_paid state.
     *
     * @return void
     * @throws BadRequestValidationFailureException
     */
    public function validateInvoicePayable()
    {
        $invoice = $this->entity;
        $status  = $invoice->getStatus();

        $typeLabel = $invoice->getTypeLabel();

        if ($invoice->trashed() === true)
        {
            throw new BadRequestValidationFailureException(
                $typeLabel . ' is not payable as it is deleted.');
        }

        if (in_array($status, [Status::ISSUED, Status::PARTIALLY_PAID], true) === false)
        {
            $message = $typeLabel . ' is not payable in ' . $status . ' status.';

            throw new BadRequestValidationFailureException($message);
        }

        // Link partially paid past expiry with payments blocked (feature: BLOCK_PL_PAY_POST_EXPIRY)
        if (($invoice->isTypeLink() === true) and
            ($invoice->isPastExpireBy() === true) and
            ($invoice->merchant->isFeatureEnabled(Features::BLOCK_PL_PAY_POST_EXPIRY) === true))
        {
            $message = $typeLabel . ' is not payable post its expiry time';

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
        else if (($invoice->isExpired() === true) and ($invoice->isTypeInvoice() === true))
        {
            throw new BadRequestValidationFailureException("$label with id $id is expired");
        }
        // Link partially paid past expiry with payments blocked (feature: BLOCK_PL_PAY_POST_EXPIRY)
        // Should act like expired for these cases so throwing error
        else if (($invoice->isPartiallyPaid() === true) and
                 ($invoice->isTypeLink() === true) and
                 ($invoice->isPastExpireBy() === true) and
                 ($invoice->merchant->isFeatureEnabled(Features::BLOCK_PL_PAY_POST_EXPIRY) === true))
        {
            throw new BadRequestValidationFailureException("$label with id $id is past its expiry");
        }

        if ($invoice->merchant->isSuspended() === true)
        {
            throw new BadRequestValidationFailureException("This account is suspended");
        }

        if ($invoice->isTypeLink() === true)
        {
            if ($invoice->isExpired() === true)
            {
                $exception = new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null,
                    'this payment link was expired.');

                $exception->getError()->setMetadata($this->getErrorMetaDataForEndState($invoice));

                throw $exception;
            }

            if ($invoice->isCancelled() === true)
            {
                $exception = new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null,
                    'this payment link was cancelled.');

                $exception->getError()->setMetadata($this->getErrorMetaDataForEndState($invoice));

                throw $exception;
            }
        }
    }

    protected function getErrorMetaDataForEndState(Entity $invoice)
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'];

        $repo = $app['repo'];

        $description = 'this payment link was ';

        if ($invoice->isExpired() === true)
        {
            $description .= 'expired.';
        }

        if ($invoice->isCancelled() === true)
        {
            $description .= 'cancelled.';
        }

        $returnArray = [
            'use_end_state_format' => true,
            'description'          => $description,
            'customerMsg'          => 'For any queries, please contact',
            'merchant_name'        => $invoice->getMerchantLabel(),
        ];

        $merchant = $invoice->merchant;

        $supportDetails = $repo->merchant_email->getEmailByType(Merchant\Email\Type::SUPPORT, $merchant->getId());

        if ($supportDetails !== null)
        {
            $supportDetails = $supportDetails->toArrayPublic();

            $returnArray += [
                'support_email'  => $supportDetails[Merchant\Email\Entity::EMAIL],
                'support_mobile' => $supportDetails[Merchant\Email\Entity::PHONE]
            ];
        }

        $exemptCustomerFlagging = $merchant->isFeatureEnabled(Feature\Constants::APPS_EXEMPT_CUSTOMER_FLAGGING);

        if (($exemptCustomerFlagging === false) && ($mode === Mode::LIVE))
        {
            $reportBaseUrl = $app['config']->get('app.customer_flagging_report_url');

            $params = http_build_query([
                'e'  => base64_encode($invoice->getPublicId()),
                's'  => base64_encode('hosted'),
            ]);

            $reportUrl = $reportBaseUrl . $params;

            $returnArray += [
                'report_link_url' => $reportUrl,
            ];
        }

      return $returnArray;
    }

    /**
     * Validator function gets called from spine against custom rule defined
     * in above rules e.g. createRules etc.
     * @param string $attribute
     * @param array  $value
     */
    public function validateLineItems(string $attribute, array $value)
    {
        $this->validateLineItemsCount(count($value));
    }

    /**
     * Given a line items count validates that it is within limit.
     * @see validateLineItems
     * @param int $lineItemsCount
     * @throws BadRequestValidationFailureException
     */
    public function validateLineItemsCount(int $lineItemsCount = -1)
    {
        $invoice             = $this->entity;
        $merchant            = $invoice->merchant;
        $maxAllowedLineItems = $this->getMaxAllowedLineItemsForMerchant($merchant);

        if ($lineItemsCount === -1) {
            $lineItemsCount = $invoice->lineItems()->count();
        }

        if ($lineItemsCount > $maxAllowedLineItems)
        {
            $message = 'The invoice may not have more than ' . $maxAllowedLineItems . ' items in total.';

            throw new BadRequestValidationFailureException(
                $message,
                Entity::LINE_ITEMS,
                [
                    Entity::ID                => $invoice->getId(),
                    'max_allowed_line_items'  => $maxAllowedLineItems,
                    'actual_line_items_count' => $lineItemsCount,
                ]);
        }
    }

    protected function getMaxAllowedLineItemsForMerchant(Merchant\Entity $merchant): int
    {
        if ($this->entity->getSubscriptionId() !== null ) {
            // Allow self::MAX_ALLOWED_LINE_ITEMS_EXPERIMENTAL for subscription type invoice
            return self::MAX_ALLOWED_LINE_ITEMS_EXPERIMENTAL;
        }

        $variant = app()->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::INV_INCREASED_LINE_ITEMS_CAP,
            // No need to maintain experiments per mode.
            Mode::LIVE);

        return strtolower($variant) === 'on' ?
            self::MAX_ALLOWED_LINE_ITEMS_EXPERIMENTAL :
            self::MAX_ALLOWED_LINE_ITEMS;
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

    public function validateCancelInvoicesOfBatch(array $batch)
    {
        if (in_array($batch[Batch\Entity::STATUS], Batch\Status::BATCH_STATUSES_VALID_FOR_CANCEL) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_STATUS_INVALID_FOR_CANCEL,
                $batch[Batch\Entity::STATUS],
                [
                    'batch_id'  => $batch[Batch\Entity::ID],
                    'status'    => $batch[Batch\Entity::STATUS],
                ]
            );
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

    protected function validateInvoiceIssueForDCCEInvoiceType(Entity $invoice)
    {
        $invoiceAmount = $invoice->getAmount();

        if ($invoiceAmount === null)
        {
            throw new BadRequestValidationFailureException('amount cannot be empty.');
        }

        $refNum = $invoice->getRefNum();

        // For ref_num need to do empty() check as it is 'sometimes' in Validator.
        if (empty($refNum) === true)
        {
            throw new BadRequestValidationFailureException('ref_num is required.');
        }
    }

    public function validateExternalEntity()
    {
        $invoice = $this->entity;

        if (in_array($invoice->getEntityType(), self::$validExternalEntities) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid External Entity',
                'entity_type'
                );
        }
    }

    public function validateCurrency(string $attribute, string $currency)
    {
        $invoice = $this->entity;

        $international = $invoice->merchant->isInternational();

        // Non International accounts should not create PL in other currencies.
        if (($international !== true) and ($currency !==  $invoice->merchant->getCurrency()))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_INTERNATIONAL_NOT_ENABLED,
                null,
                [
                    'currency' => $currency
                ]);
        }
    }

    /**
     * @param array $input
     * Rate limit on number of invoice creation in Bulk Route
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateBulkInvoiceCount(array $input)
    {
        if (count($input) > self::MAX_BULK_INVOICES_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Max Limit of Bulk Invoice is ' . self::MAX_BULK_INVOICES_LIMIT,
                null,
                null
            );
        }
    }

    public function validateOfferAmountIfSubscription($subscriptionId)
    {
        $invoice = $this->entity;

        // Non International accounts should not create PL in other currencies.
        if ($invoice->getOfferAmount() !== null and isset($subscriptionId) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PRODUCT_OFFER_AMOUNT_NOT_SUPPORTED,
                null,
                [
                    'offerAmount' => $invoice->getOfferAmount()
                ]);
        }
    }

    public function validateLinkShouldBeFound()
    {
        $invoice = $this->entity;

        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'];

        if (($invoice->getType() === Type::LINK) &&
            (in_array($invoice->getStatus(), Entity::UPDATE_BLOCKED_END_STATES) === true))
        {
            $oldTimestamp = Carbon::today(Timezone::IST)->subDays(180)->getTimestamp();

            if (($invoice->getCreatedAt() < $oldTimestamp) === true)
            {
                $data = [
                    'model' => Entity::class,
                    'attributes' => $invoice->getPublicId(),
                    'operation'  => 'find',
                    'message'    => 'This link cannot be retrieved now since it is older than six months.'
                ];

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
            }
        }
    }
}
