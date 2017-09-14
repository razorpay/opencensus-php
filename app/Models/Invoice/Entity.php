<?php

namespace RZP\Models\Invoice;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Customer;
use RZP\Models\Address;
use RZP\Models\LineItem;
use RZP\Models\FileStore;
use RZP\Models\Plan\Subscription;
use RZP\Exception\LogicException;
use RZP\Models\Base\Traits\NotesTrait;


class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    /**
     * Prefix for pdf file name
     */
    const PDF_PREFIX               = 'pdfs/';

    // ------------------ Entity Keys --------------------------------

    const ORDER_ID                 = 'order_id';
    const RECEIPT                  = 'receipt';
    const INVOICE_NUMBER           = 'invoice_number';
    const MERCHANT_ID              = 'merchant_id';
    const SUBSCRIPTION_ID          = 'subscription_id';
    const BATCH_ID                 = 'batch_id';
    const CUSTOMER_ID              = 'customer_id';
    const CUSTOMER_NAME            = 'customer_name';
    const CUSTOMER_EMAIL           = 'customer_email';
    const CUSTOMER_CONTACT         = 'customer_contact';
    const CUSTOMER_BILLING_ADDR_ID = 'customer_billing_addr_id';
    const STATUS                   = 'status';
    const SUBSCRIPTION_STATUS      = 'subscription_status';
    const DATE                     = 'date';
    const DUE_BY                   = 'due_by';
    const SCHEDULED_AT             = 'scheduled_at';
    const ISSUED_AT                = 'issued_at';
    const PAID_AT                  = 'paid_at';
    const CANCELLED_AT             = 'cancelled_at';
    const EXPIRED_AT               = 'expired_at';
    const EXPIRE_BY                = 'expire_by';
    const EMAIL_STATUS             = 'email_status';
    const SMS_STATUS               = 'sms_status';
    const DESCRIPTION              = 'description';
    const TERMS                    = 'terms';
    const NOTES                    = 'notes';
    const COMMENT                  = 'comment';
    const SHORT_URL                = 'short_url';
    const VIEW_LESS                = 'view_less';

    /**
     * If set to true, partial payments would be accepted
     * against this invoice and invoice status would move
     * to PARTIALLY_PAID in those cases.
     * Once there is no due amount left, it goes to PAID.
     */
    const PARTIAL_PAYMENT          = 'partial_payment';

    const GROSS_AMOUNT             = 'gross_amount';
    const TAX_AMOUNT               = 'tax_amount';
    const AMOUNT                   = 'amount';

    /**
     * Following two attributes are looked up from corresponding
     * order entity. Order maintains 'amount_paid'. If no order
     * has been created for invoice till now, followings will be
     * null.
     */
    const AMOUNT_PAID              = 'amount_paid';
    const AMOUNT_DUE               = 'amount_due';
    const CURRENCY                 = 'currency';
    const USER_ID                  = 'user_id';
    const SOURCE                   = 'source';
    const BILLING_START            = 'billing_start';
    const BILLING_END              = 'billing_end';
    const TYPE                     = 'type';

    /**
     * This is consumed by clients, if set to true they would show
     * taxes and discounts of all line items grouped an once at
     * the bottom of invoice.
     */
    const GROUP_TAXES_DISCOUNTS    = 'group_taxes_discounts';


    /**
     * Post payment hosted page sends back control to following
     * callback URL via specified method (currently only GET).
     */
    const CALLBACK_URL             = 'callback_url';
    const CALLBACK_METHOD          = 'callback_method';

    const DELETED_AT               = 'deleted_at';

    // ---------------------- Input Keys -----------------------------

    const LINE_ITEMS               = 'line_items';
    const CUSTOMER                 = 'customer';
    const EMAIL_NOTIFY             = 'email_notify';
    const SMS_NOTIFY               = 'sms_notify';
    const DRAFT                    = 'draft';
    const BATCH_IDS                = 'batch_ids';
    const TYPES                    = 'types';

    // ---------------------- Input Keys End -------------------------

    // ------------------------- Output Keys -------------------------

    const CUSTOMER_DETAILS         = 'customer_details';
    const PAYMENT_ID               = 'payment_id';
    const URL                      = 'url';

    // ------------------------ Output Keys End ----------------------


    const EMAIL                    = 'email';
    const SMS                      = 'sms';
    const ITEMS                    = 'items';
    const IS_PAID                  = 'is_paid';

    const DEFAULT_DUE_DAYS         = 60;

    //
    // For now the default value is same across merchants,
    // later it can be configurable at merchant's level.
    //
    const DEFAULT_EXPIRY_DAYS      = 60;

    // ------------------------ Relation Keys ------------------------

    const ORDER                    = 'order';
    const PAYMENTS                 = 'payments';

    protected static $sign         = 'inv';

    protected $entity              = 'invoice';

    protected $generateIdOnCreate  = true;

    protected $embeddedRelations   = [
        self::LINE_ITEMS,
    ];

    protected $validOperations = [
        // Core's actions
        'create',
        'update',
        'delete',
        'issue',
        'cancelInvoice',
        'expireInvoice',
        'sendNotification',
        'sendSubscriptionNotification',
        'addLineItems',
        'addManyLineItems',
        'updateLineItem',
        'removeLineItem',
        'removeManyLineItems',

        // Notifier's actions
        'notifyInvoiceIssued',
        'notifyInvoiceExpired',
    ];

    protected $defaults = [
        self::ORDER_ID                 => null,
        self::STATUS                   => Status::ISSUED,
        self::SUBSCRIPTION_STATUS      => null,
        self::SUBSCRIPTION_ID          => null,
        self::DATE                     => null,
        self::ISSUED_AT                => null,
        self::PAID_AT                  => null,
        self::CANCELLED_AT             => null,
        self::EXPIRED_AT               => null,
        self::EXPIRE_BY                => null,
        self::RECEIPT                  => null,
        self::DESCRIPTION              => null,
        self::NOTES                    => [],
        self::COMMENT                  => null,
        self::SHORT_URL                => null,
        self::VIEW_LESS                => 1,
        self::TYPE                     => Type::INVOICE,
        self::USER_ID                  => null,
        self::PARTIAL_PAYMENT          => false,
        self::GROSS_AMOUNT             => null,
        self::TAX_AMOUNT               => null,
        self::AMOUNT                   => null,
        self::CURRENCY                 => 'INR',
        self::BILLING_START            => null,
        self::BILLING_END              => null,
        self::CUSTOMER_NAME            => null,
        self::CUSTOMER_EMAIL           => null,
        self::CUSTOMER_CONTACT         => null,
        self::CUSTOMER_BILLING_ADDR_ID => null,
        self::GROUP_TAXES_DISCOUNTS    => false,
        self::CALLBACK_URL             => null,
        self::CALLBACK_METHOD          => null,
    ];

    protected static $generators = [
        self::DATE,
        self::DUE_BY,
        self::SCHEDULED_AT,
        self::EMAIL_STATUS,
        self::SMS_STATUS,
        self::STATUS,
    ];

    protected $fillable = [
        self::EMAIL_STATUS,
        self::SMS_STATUS,
        self::DATE,
        self::TERMS,
        self::PARTIAL_PAYMENT,
        self::AMOUNT,
        self::DESCRIPTION,
        self::NOTES,
        self::COMMENT,
        self::RECEIPT,
        self::VIEW_LESS,
        self::CURRENCY,
        self::SOURCE,
        self::TYPE,
        self::BILLING_START,
        self::BILLING_END,
        self::USER_ID,
        self::EXPIRE_BY,
        self::CALLBACK_URL,
        self::CALLBACK_METHOD,
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::RECEIPT,
        self::INVOICE_NUMBER,
        self::STATUS,
        self::SUBSCRIPTION_STATUS,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
        self::SUBSCRIPTION_ID,
        self::ORDER_ID,
        self::PAYMENT_ID,
        self::DUE_BY,
        self::EXPIRED_AT,
        self::EXPIRE_BY,
        self::SCHEDULED_AT,
        self::ISSUED_AT,
        self::PAID_AT,
        self::CANCELLED_AT,
        self::CUSTOMER_DETAILS,
        self::SMS_STATUS,
        self::EMAIL_STATUS,
        self::MERCHANT_ID,
        self::DATE,
        self::DESCRIPTION,
        self::TERMS,
        self::NOTES,
        self::COMMENT,
        self::CURRENCY,
        self::SHORT_URL,
        self::VIEW_LESS,
        self::SOURCE,
        self::TYPE,
        self::PARTIAL_PAYMENT,
        self::GROUP_TAXES_DISCOUNTS,
        self::CALLBACK_URL,
        self::CALLBACK_METHOD,
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::BILLING_START,
        self::BILLING_END,
        self::GROSS_AMOUNT,
        self::TAX_AMOUNT,
        self::USER_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::RECEIPT,
        self::INVOICE_NUMBER,
        self::CUSTOMER_ID,
        self::CUSTOMER_DETAILS,
        self::ORDER_ID,
        self::SUBSCRIPTION_ID,
        self::LINE_ITEMS,
        self::PAYMENT_ID,
        self::PAYMENTS,
        self::STATUS,
        self::EXPIRE_BY,
        self::ISSUED_AT,
        self::PAID_AT,
        self::CANCELLED_AT,
        self::EXPIRED_AT,
        self::SMS_STATUS,
        self::EMAIL_STATUS,
        self::DATE,
        self::TERMS,
        self::PARTIAL_PAYMENT,
        self::GROSS_AMOUNT,
        self::TAX_AMOUNT,
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::CURRENCY,
        self::DESCRIPTION,
        self::NOTES,
        self::COMMENT,
        self::SHORT_URL,
        self::VIEW_LESS,
        self::BILLING_START,
        self::BILLING_END,
        self::TYPE,
        self::GROUP_TAXES_DISCOUNTS,
        self::USER_ID,
        self::CREATED_AT,
    ];

    protected $appends = [
        self::PUBLIC_ID,
        self::ENTITY,
        self::CUSTOMER_DETAILS,
        self::PAYMENT_ID,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::INVOICE_NUMBER,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::ORDER_ID,
        self::SUBSCRIPTION_ID,
    ];

    protected $casts = [
        self::VIEW_LESS             => 'bool',
        self::PARTIAL_PAYMENT       => 'bool',
        self::GROSS_AMOUNT          => 'int',
        self::TAX_AMOUNT            => 'int',
        self::AMOUNT                => 'int',
        self::AMOUNT_PAID           => 'int',
        self::AMOUNT_DUE            => 'int',
        self::GROUP_TAXES_DISCOUNTS => 'bool',
    ];

    protected $amounts = [
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
    ];

    /**
     * Reports currently works for type:link only.
     *
     * @todo: Plan and spit link, invoices.
     *
     * @var array
     */
    protected $hiddenInReport = [
        self::INVOICE_NUMBER,
        self::CUSTOMER_DETAILS,
        self::ORDER_ID,
        self::SUBSCRIPTION_ID,
        self::LINE_ITEMS,
        self::PAYMENT_ID,
        self::GROSS_AMOUNT,
        self::TAX_AMOUNT,
        self::COMMENT,
        self::VIEW_LESS,
        self::BILLING_START,
        self::BILLING_END,
        self::TYPE,
        self::GROUP_TAXES_DISCOUNTS,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DATE,
        self::EXPIRE_BY,
        self::ISSUED_AT,
        self::PAID_AT,
        self::EXPIRED_AT,
        self::CANCELLED_AT,
    ];

    // -------------------------------------- Mutators ---------------

    // Following 2 mutators are for converting '' (empty strings)
    // input to null.

    public function setDateAttribute($date)
    {
        if (empty($date) === true)
        {
            $date = null;
        }

        $this->attributes[self::DATE] = $date;
    }

    public function setExpireByAttribute($expireBy)
    {
        if (empty($expireBy) === true)
        {
            $expireBy = null;
        }

        $this->attributes[self::EXPIRE_BY] = $expireBy;
    }

    // -------------------------------------- End Mutators -----------

    // -------------------------------------- Getters ----------------

    public function getEmailStatus()
    {
        return $this->getAttribute(self::EMAIL_STATUS);
    }

    public function getSmsStatus()
    {
        return $this->getAttribute(self::SMS_STATUS);
    }

    public function getCustomerName()
    {
        return $this->getAttribute(self::CUSTOMER_NAME);
    }

    public function getCustomerEmail()
    {
        return $this->getAttribute(self::CUSTOMER_EMAIL);
    }

    public function getCustomerContact()
    {
        return $this->getAttribute(self::CUSTOMER_CONTACT);
    }

    public function getScheduledAt()
    {
        return $this->getAttribute(self::SCHEDULED_AT);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getShortUrl()
    {
        return $this->getAttribute(self::SHORT_URL);
    }

    public function getOrderId()
    {
        return $this->getAttribute(self::ORDER_ID);
    }

    public function isPartialPaymentAllowed()
    {
        return $this->getAttribute(self::PARTIAL_PAYMENT);
    }

    public function getGrossAmount()
    {
        return $this->getAttribute(self::GROSS_AMOUNT);
    }

    public function getAmountPaid()
    {
        return $this->getAttribute(self::AMOUNT_PAID);
    }

    public function getAmountDue()
    {
        return $this->getAttribute(self::AMOUNT_DUE);
    }

    public function getFormattedAmount()
    {
        return number_format($this->getAmount() / 100, 2);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getViewLess()
    {
        return $this->getAttribute(self::VIEW_LESS);
    }

    public function getSubscriptionStatus()
    {
        return $this->getAttribute(self::SUBSCRIPTION_STATUS);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getSubscriptionId()
    {
        return $this->getAttribute(self::SUBSCRIPTION_ID);
    }

    public function isOfSubscription()
    {
        return ($this->getAttribute(self::SUBSCRIPTION_ID) !== null);
    }

    public function getReceipt()
    {
        return $this->getAttribute(self::RECEIPT);
    }

    public function getReceiptElsePublicId()
    {
        $receipt = $this->getReceipt();

        if ($receipt !== null)
        {
            return $receipt;
        }

        return $this->getPublicId();
    }

    public function getPaidAt()
    {
        return $this->getAttribute(self::PAID_AT);
    }

    public function getIssuedAt()
    {
        return $this->getAttribute(self::ISSUED_AT);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getTypeLabel()
    {
        return Type::getLabel($this->getType());
    }

    public function getCallbackUrl()
    {
        return $this->getAttribute(self::CALLBACK_URL);
    }

    public function getCallbackMethod()
    {
        return $this->getAttribute(self::CALLBACK_METHOD);
    }

    public function hasBeenPaid()
    {
        return ($this->getStatus() === Status::PAID);
    }

    public function getDueBy()
    {
        return $this->getAttribute(self::DUE_BY);
    }

    public function getExpireBy()
    {
        return $this->getAttribute(self::EXPIRE_BY);
    }

    public function isDraft(): bool
    {
        return ($this->getStatus() === Status::DRAFT);
    }

    public function isIssued(): bool
    {
        return ($this->getStatus() === Status::ISSUED);
    }

    public function isPaid(): bool
    {
        return ($this->getStatus() === Status::PAID);
    }

    public function isCancelled(): bool
    {
        return ($this->getStatus() === Status::CANCELLED);
    }

    public function isExpired(): bool
    {
        return ($this->getStatus() === Status::EXPIRED);
    }

    public function hasCustomerBillingAddress(): bool
    {
        return ($this->getAttribute(self::CUSTOMER_BILLING_ADDR_ID) !== null);
    }

    public function isTypeLink(): bool
    {
        return ($this->getType() === Type::LINK);
    }

    public function isTypeInvoice(): bool
    {
        return ($this->getType() === Type::INVOICE);
    }

    public function isFullyPaid()
    {
        return ($this->getAmount() === $this->getAmountPaid());
    }

    public function hasSubscription()
    {
        return ($this->getAttribute(self::SUBSCRIPTION_ID) !== null);
    }

    /**
     * Returns the path component of Dashboard view url.
     *
     * For invoices (New):   #/app/invoices/{public-id}
     * Otherwise (Existing): #/app/invoices/{public-id}/details
     *
     * @return string
     */
    public function getDashboardPath(): string
    {
        $path = '#/app/invoices/' . $this->getPublicId();

        if ($this->isTypeInvoice() === false)
        {
            $path .= '/details';
        }

        return $path;
    }

    /**
     * Returns string to be used a pdf file path in s3/local store.
     * Format: pdfs/{invoiceId}_{epoch}
     *
     * @return string
     */
    public function getPdfFilename(): string
    {
        return self::PDF_PREFIX . $this->getId() . '_' . time();
    }

    public function getPdfDisplayName(): string
    {
        // Expected format:
        // Invoice <Reciept/Invoice ID> from <Company> (<Paid/Unpaid>).pdf

        $receipt = $this->getReceiptElsePublicId();
        $from    = $this->merchant->getBillingLabel();
        $status  = $this->hasBeenPaid() ? 'Paid' : 'Unpaid';
        $ext     = FileStore\Format::PDF;

        return sanitizeFilename("Invoice $receipt from $from ($status).$ext");
    }

    // -------------------------------------- End Getters ------------


    // -------------------------------------- Setters ----------------

    public function setCustomerDetails(Customer\Entity $customer)
    {
        if (empty($customer))
        {
            return;
        }

        $this->setCustomerName($customer->getName());
        $this->setCustomerContact($customer->getContact());
        $this->setCustomerEmail($customer->getEmail());

        //
        // Sets billing address
        //

        $repo = App::getFacadeRoot()['repo'];

        $billingAddress = $repo->address
                               ->fetchPrimaryAddressOfEntityOfType(
                                    $customer,
                                    Address\Type::BILLING_ADDRESS);

        if ($billingAddress !== null)
        {
            $this->setCustomerBillingAddrId($billingAddress->getId());
        }
    }

    public function setCustomerName($customerName)
    {
        $this->setAttribute(self::CUSTOMER_NAME, $customerName);
    }

    public function setCustomerBillingAddrId(string $customerBillingAddressId)
    {
        $this->setAttribute(
            self::CUSTOMER_BILLING_ADDR_ID, $customerBillingAddressId);
    }

    public function setCustomerEmail($customerEmail)
    {
        $this->setAttribute(self::CUSTOMER_EMAIL, $customerEmail);
    }

    public function setCustomerContact($customerContact)
    {
        $this->setAttribute(self::CUSTOMER_CONTACT, $customerContact);
    }

    public function setSmsStatus($status)
    {
        if ($status !== null)
        {
            NotifyStatus::checkStatus($status);
        }

        $this->setAttribute(self::SMS_STATUS, $status);
    }

    public function setEmailStatus($status)
    {
        if ($status !== null)
        {
            NotifyStatus::checkStatus($status);
        }

        $this->setAttribute(self::EMAIL_STATUS, $status);
    }

    public function setStatus(string $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);

        // Sets corresponding timestamps as per new status
        if (in_array($status, Status::$timestampedStatuses, true) === true)
        {
            $timestampKey = $status . '_at';
            $currentTime = Carbon::now()->getTimestamp();

            $this->setAttribute($timestampKey, $currentTime);
        }
    }

    public function setSubscriptionStatus(string $subscriptionStatus)
    {
        Status::checkSubscriptionStatus($subscriptionStatus);

        $this->setAttribute(self::SUBSCRIPTION_STATUS, $subscriptionStatus);
    }

    public function setShortUrl(string $shortUrl)
    {
        $this->setAttribute(self::SHORT_URL, $shortUrl);
    }

    public function setBillingStart(int $billingStart)
    {
        $this->setAttribute(self::BILLING_START, $billingStart);
    }

    public function setBillingEnd(int $billingEnd)
    {
        $this->setAttribute(self::BILLING_END, $billingEnd);
    }

    public function setGrossAmount(int $amount)
    {
        $this->setAttribute(self::GROSS_AMOUNT, $amount);
    }

    public function setTaxAmount(int $amount)
    {
        $this->setAttribute(self::TAX_AMOUNT, $amount);
    }

    public function setAmount(int $amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    /**
     * Sets all amounts field to null.
     * Used when all line items of draft invoice are removed.
     *
     * 'null' represents 'not set', 0 can be at some later time a valid value.
     * We use the same during validations also.
     */
    public function setAmountsToNull()
    {
        $this->setAttribute(self::GROSS_AMOUNT, null);
        $this->setAttribute(self::TAX_AMOUNT, null);
        $this->setAttribute(self::AMOUNT, null);
    }

    /**
     * Updates invoice status post capture.
     * If all amount has been paid, move to PAID else PARTIALLY_PAID.
     */
    public function updateStatusPostCapture()
    {
        $newStatus = ($this->isFullyPaid() === true) ?
                        Status::PAID : Status::PARTIALLY_PAID;

        $this->setStatus($newStatus);
    }

    // -------------------------------------- End Setters ------------

    // -------------------------------------- Accessors --------------

    /**
     * Gets customer_details attribute of invoice entity.
     *
     * Invoice has association with customer and customer_billing_addr_id. A
     * customer's detail and it's primary billing address can be edited anytime.
     * We keep customer's basic attribute in invoice entity as a snapshot. This
     * attribute returns those.
     *
     * @return array
     */
    protected function getCustomerDetailsAttribute(): array
    {
        $customerDetails = [
            Customer\Entity::NAME            => $this->getAttribute(self::CUSTOMER_NAME),
            Customer\Entity::EMAIL           => $this->getAttribute(self::CUSTOMER_EMAIL),
            Customer\Entity::CONTACT         => $this->getAttribute(self::CUSTOMER_CONTACT),
            Customer\Entity::BILLING_ADDRESS => null,

            // For backward compatibility.
            self::CUSTOMER_NAME    => $this->getAttribute(self::CUSTOMER_NAME),
            self::CUSTOMER_EMAIL   => $this->getAttribute(self::CUSTOMER_EMAIL),
            self::CUSTOMER_CONTACT => $this->getAttribute(self::CUSTOMER_CONTACT),
        ];

        if ($this->hasCustomerBillingAddress() === true)
        {
            $billingAddress = $this->customerBillingAddress->toArrayPublic();

            $customerDetails[Customer\Entity::BILLING_ADDRESS] = $billingAddress;
        }

        return $customerDetails;
    }

    protected function getPaymentIdAttribute()
    {
        $orderId = $this->getOrderId();

        //
        // Order gets created when invoice moves in ISSUED state.
        // Order Id will be null for invoices in draft status.
        //
        if ($orderId === null)
        {
            return null;
        }

        $repo = App::getFacadeRoot()['repo'];

        $payment = $repo->payment->getCapturedPaymentForOrder($orderId);

        if ($payment !== null)
        {
            return $payment->getPublicId();
        }

        return null;
    }

    /**
     * Looks up amount_paid attribute from corresponding
     * order entity.
     *
     * @return null|int
     */
    public function getAmountPaidAttribute()
    {
        if ($this->getOrderId() === null)
        {
            return null;
        }

        return $this->order->getAmountPaid();
    }

    /**
     * Looks up amount_due attribute from corresponding
     * order entity.
     *
     * @return null|int
     */
    public function getAmountDueAttribute()
    {
        if ($this->getOrderId() === null)
        {
            return null;
        }

        return $this->order->getAmountDue();
    }

    public function getInvoiceNumberAttribute()
    {
        return $this->getAttribute(self::RECEIPT);
    }

    // -------------------------------------- End Accessors ----------

    // -------------------------------------- Public Setters ---------

    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    protected function setPublicOrderIdAttribute(array & $array)
    {
        $orderId = $this->getAttribute(self::ORDER_ID);

        $array[self::ORDER_ID] = Order\Entity::getSignedIdOrNull($orderId);
    }

    protected function setPublicSubscriptionIdAttribute(array & $array)
    {
        $subscriptionId = $this->getAttribute(self::SUBSCRIPTION_ID);

        if ($subscriptionId !== null)
        {
            $array[self::SUBSCRIPTION_ID] = Subscription\Entity::getSignedIdOrNull($subscriptionId);
        }
        else
        {
            unset($array[Entity::SUBSCRIPTION_ID]);
        }
    }

    protected function setPublicUserIdAttribute(array & $array)
    {
        $type = $this->getAttribute(self::TYPE);

        if ($type === Type::ECOD)
        {
            $array[self::USER_ID] = $this->getAttribute(self::USER_ID);
        }
        else
        {
            unset($array[self::USER_ID]);
        }
    }

    // -------------------------------------- End Public Setters -----

    // -------------------------------------- Generators -------------

    public function generateDate(array $input)
    {
        // If DATE is not sent in input, set it to now
        // If DATE is sent, even as null use that only(so not using isset)
        if (array_key_exists(Entity::DATE, $input) === false)
        {
            $now = Carbon::now()->getTimestamp();

            $this->setAttribute(self::DATE, $now);
        }
    }

    public function generateEmailStatus(array $input)
    {
        $this->setAttribute(self::EMAIL_STATUS, NotifyStatus::PENDING);

        // Should not use `empty` because the value can be 0
        if ((isset($input[self::EMAIL_NOTIFY]) === true) and
            ($input[self::EMAIL_NOTIFY] === '0'))
        {
            $this->setAttribute(self::EMAIL_STATUS, null);
        }
    }

    public function generateSmsStatus(array $input)
    {
        $this->setAttribute(self::SMS_STATUS, NotifyStatus::PENDING);

        // Should not use `empty` because the value can be 0
        if ((isset($input[self::SMS_NOTIFY]) === true) and
            ($input[self::SMS_NOTIFY] === '0'))
        {
            $this->setAttribute(self::SMS_STATUS, null);
        }
    }

    public function generateDueBy(array $input)
    {
        if (empty($input[self::DUE_BY]) === false)
        {
            $dueBy = $input[self::DUE_BY];
        }
        else
        {
            $dueBy = Carbon::now(Timezone::IST)
                           ->addDays(self::DEFAULT_DUE_DAYS)
                           ->getTimestamp();
        }

        $this->setAttribute(self::DUE_BY, $dueBy);
    }

    public function generateScheduledAt(array $input)
    {
        if (empty($input[self::SCHEDULED_AT]) === false)
        {
            $scheduledAt = $input[self::SCHEDULED_AT];
        }
        else
        {
            $scheduledAt = Carbon::now()->getTimestamp();
        }

        $this->setAttribute(self::SCHEDULED_AT, $scheduledAt);
    }

    /**
     * Generates status based on draft key's value sent in request param.
     * If sent to 0, means created/stays in DRAFT status, otherwise if sent to 1,
     * means will be moved to ISSUED state.
     *
     * @param array $input
     *
     * @return null
     */
    public function generateStatus(array $input)
    {
        if (isset($input[self::DRAFT]) and ($input[self::DRAFT] === '1'))
        {
            $this->setStatus(Status::DRAFT);
        }
        else
        {
            $this->setStatus(Status::ISSUED);
        }
    }

    // -------------------------------------- End Generators ---------

    // -------------------------------------- Relations --------------

    public function order()
    {
        return $this->belongsTo('RZP\Models\Order\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function lineItems()
    {
        return $this->morphMany('RZP\Models\LineItem\Entity', 'entity');
    }

    public function subscription()
    {
        return $this->belongsTo('RZP\Models\Plan\Subscription\Entity');
    }

    /**
     * The batch which created this invoice entity.
     *
     * @return null|\Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function batch()
    {
        return $this->belongsTo('RZP\Models\Batch\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function customerBillingAddress()
    {
        return $this->belongsTo(
            'RZP\Models\Address\Entity', 'customer_billing_addr_id');
    }

    public function payments()
    {
        return $this->hasMany('RZP\Models\Payment\Entity');
    }

    public function files()
    {
        return $this->morphMany('RZP\Models\FileStore\Entity', 'entity');
    }

    /**
     * Gets the most recent invoice pdf file
     *
     * @return FileStore\Entity
     */
    public function pdf(): FileStore\Entity
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, '=', FileStore\Type::INVOICE_PDF)
                    ->latest()
                    ->first();
    }

    // -------------------------------------- End Relations ----------

    public function getValidOperations(): array
    {
        return $this->validOperations;
    }

    // -------------------------------------- Serializations ---------

    public function toArrayReport()
    {
        if ($this->isTypeLink() === false)
        {
            throw new LogicException('Report not available for types other than link');
        }

        $report = parent::toArrayReport();

        // Add flattened customer details in report

        $report[self::CUSTOMER_NAME]    = $this->getCustomerName();
        $report[self::CUSTOMER_EMAIL]   = $this->getCustomerEmail();
        $report[self::CUSTOMER_CONTACT] = $this->getCustomerContact();

        return $report;
    }
}
