<?php

namespace RZP\Models\Invoice;

use App;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Customer;
use RZP\Models\Order;
use RZP\Models\Plan\Subscription;
use RZP\Models\Address;
use RZP\Models\FileStore;

class Entity extends Base\PublicEntity
{
    const PDF_PREFIX = 'pdfs/';

    use NotesTrait;

    use SoftDeletes;

    // ------------------ Entity Keys --------------------------------

    const ORDER_ID                 = 'order_id';
    const RECEIPT                  = 'receipt';
    const MERCHANT_ID              = 'merchant_id';
    const SUBSCRIPTION_ID          = 'subscription_id';
    const CUSTOMER_ID              = 'customer_id';
    const CUSTOMER_NAME            = 'customer_name';
    const CUSTOMER_EMAIL           = 'customer_email';
    const CUSTOMER_BILLING_ADDR_ID = 'customer_billing_addr_id';
    const CUSTOMER_CONTACT         = 'customer_contact';
    const STATUS                   = 'status';
    const SUB_STATUS               = 'sub_status';
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
    const AMOUNT                   = 'amount';
    const CURRENCY                 = 'currency';
    const USER_ID                  = 'user_id';
    const SOURCE                   = 'source';
    const BILLING_START            = 'billing_start';
    const BILLING_END              = 'billing_end';
    const TYPE                     = 'type';
    const DELETED_AT               = 'deleted_at';

    // ---------------------- Input Keys -------------------------------------

    const LINE_ITEMS               = 'line_items';
    const CUSTOMER                 = 'customer';
    const EMAIL_NOTIFY             = 'email_notify';
    const SMS_NOTIFY               = 'sms_notify';
    const DRAFT                    = 'draft';

    // ---------------------- Input Keys End -------------------------------------

    // ------------------------- Output Keys --------------------------------------

    const CUSTOMER_DETAILS         = 'customer_details';
    const CUSTOMER_ADDRESS         = 'customer_address';
    const CUSTOMER_BILLING_ADDRESS = 'customer_billing_address';
    const PAYMENT_ID               = 'payment_id';

    // ------------------------ Output Keys End -----------------------------------


    const EMAIL                    = 'email';
    const SMS                      = 'sms';
    const ITEMS                    = 'items';

    const DEFAULT_DUE_DAYS         = 60;

    //
    // For now the default value is same across merchants,
    // later it can be configurable at merchant's level.
    //
    const DEFAULT_EXPIRY_DAYS      = 60;

    protected static $sign         = 'inv';

    protected $entity              = 'invoice';

    protected $generateIdOnCreate  = true;

    protected $validOperations = [
        'create',
        'update',
        'delete',
        'cancelInvoice',
        'expireInvoice',
        'sendNotification',
        'sendSubscriptionNotification',
        'addLineItems',
        'addManyLineItems',
        'updateLineItem',
        'removeLineItem',
        'removeManyLineItems',
    ];

    protected $defaults = [
        // This is null by default because we don't create an order
        // when the invoice is being generated in a draft state.
        self::ORDER_ID                 => null,
        // For a draft state, it has to be sent explicitly in the request.
        // It's created in the issued state otherwise.
        self::STATUS                   => Status::ISSUED,
        self::SUB_STATUS               => null,
        // self::ADJUSTMENT            => 0,
        // self::SHIPPING              => 0,
        self::SUBSCRIPTION_ID          => null,
        self::DATE                     => null,
        self::ISSUED_AT                => null,
        self::PAID_AT                  => null,
        self::CANCELLED_AT             => null,
        self::EXPIRED_AT               => null,
        self::RECEIPT                  => null,
        self::DESCRIPTION              => null,
        self::NOTES                    => [],
        self::COMMENT                  => null,
        self::SHORT_URL                => null,
        self::VIEW_LESS                => 1,
        self::TYPE                     => Type::INVOICE,
        self::USER_ID                  => null,
        self::AMOUNT                   => null,
        self::CURRENCY                 => 'INR',
        self::BILLING_START            => null,
        self::BILLING_END              => null,
        self::CUSTOMER_NAME            => null,
        self::CUSTOMER_EMAIL           => null,
        self::CUSTOMER_CONTACT         => null,
        self::CUSTOMER_BILLING_ADDR_ID => null,
    ];

    // Generates fields to be filled in the DB.
    // No validation performed on these fields.
    protected static $generators = [
        // self::DISCOUNT,
        self::DATE,
        self::DUE_BY,
        self::SCHEDULED_AT,
        self::EMAIL_STATUS,
        self::SMS_STATUS,
        self::STATUS,
    ];

    // Fields that can be inserted by ->fill() directly
    // This array should also include the fields mentioned in the generator.
    protected $fillable = [
        // self::DUE_BY,
        // self::SCHEDULED_AT,
        self::EMAIL_STATUS,
        self::SMS_STATUS,
        self::DATE,
        self::TERMS,
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
        // self::ADJUSTMENT,
        // self::SHIPPING,
        // self::DISCOUNT,
    ];

    // Fields to be exposed by the entity in general
    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::RECEIPT,
        self::STATUS,
        self::SUB_STATUS,
        self::CUSTOMER_ID,
        self::CUSTOMER,
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
        self::LINE_ITEMS,
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
        self::AMOUNT,
        self::BILLING_START,
        self::BILLING_END,
        self::USER_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    // Fields to be exposed to the client
    protected $public = [
        self::ID,
        self::ENTITY,
        self::RECEIPT,
        self::CUSTOMER_ID,
        self::CUSTOMER,
        self::CUSTOMER_DETAILS,
        self::ORDER_ID,
        self::SUBSCRIPTION_ID,
        self::LINE_ITEMS,
        self::PAYMENT_ID,
        self::STATUS,
        // self::DUE_BY,
        // self::SCHEDULED_AT,
        self::EXPIRE_BY,
        self::ISSUED_AT,
        self::PAID_AT,
        self::CANCELLED_AT,
        self::EXPIRED_AT,
        self::SMS_STATUS,
        self::EMAIL_STATUS,
        self::DATE,
        self::TERMS,
        self::AMOUNT,
        self::DESCRIPTION,
        self::NOTES,
        self::COMMENT,
        self::CURRENCY,
        self::SHORT_URL,
        self::VIEW_LESS,
        self::BILLING_START,
        self::BILLING_END,
        self::TYPE,
        self::USER_ID,
        // self::TOTAL_AMOUNT,
        self::CREATED_AT,
    ];

    // Fields to be added while retrieving the entity
    protected $appends = [
        self::PUBLIC_ID,
        self::ENTITY,
        self::CUSTOMER_DETAILS,
        self::LINE_ITEMS,
        self::PAYMENT_ID,
    ];

    // The functions for these fields will be called only
    // via toArrayPublic()
    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::CUSTOMER,
        self::ORDER_ID,
        self::SUBSCRIPTION_ID,
    ];

    protected $casts = [
        self::VIEW_LESS  => 'bool',
        self::AMOUNT     => 'int',
        self::DATE       => 'int',
        self::EXPIRE_BY  => 'int',
        self::EXPIRED_AT => 'int',
    ];

    // -------------------------------------- Mutators --------------------------------------

    public function setDateAttribute($date)
    {
        // To convert '' (empty strings coming from url encoded form data) to null
        if (empty($date))
        {
            $date = null;
        }

        $this->attributes[self::DATE] = $date;
    }

    // -------------------------------------- End Mutators --------------------------------------

    // -------------------------------------- Getters --------------------------------------

    public function getEmailStatus()
    {
        return $this->getAttribute(self::EMAIL_STATUS);
    }

    public function getSmsStatus()
    {
        return $this->getAttribute(self::SMS_STATUS);
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

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getFormattedAmount()
    {
        return number_format($this->getAmount() / 100, 2);
    }

    public function getFormattedAmountWithCurrency()
    {
        return $this->getCurrency() . ' ' . number_format($this->getAmount() / 100, 2);
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

    public function getSubStatus()
    {
        return $this->getAttribute(self::SUB_STATUS);
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

    public function hasBeenPaid()
    {
        return ($this->getPaidAt() !== null);
    }

    public function getDueBy()
    {
        return $this->getAttribute(self::DUE_BY);
    }

    public function getExpireBy()
    {
        return $this->getAttribute(self::EXPIRE_BY);
    }

    public function isDraft()
    {
        return ($this->getStatus() === Status::DRAFT);
    }

    public function isIssued()
    {
        return ($this->getStatus() === Status::ISSUED);
    }

    public function isPaid()
    {
        return ($this->getStatus() === Status::PAID);
    }

    public function isCancelled()
    {
        return ($this->getStatus() === Status::CANCELLED);
    }

    public function isExpired()
    {
        return ($this->getStatus() === Status::EXPIRED);
    }

    public function hasCustomerBillingAddress()
    {
        return ($this->getAttribute(self::CUSTOMER_BILLING_ADDR_ID) !== null);
    }

    public function isTypeInvoice()
    {
        return ($this->getType() === Type::INVOICE);
    }

    /**
     * Returns the path component of Dashboard view url.
     *
     * For invoices (New):   #/app/invoices/{public-id}
     * Otherwise (Existing): #/app/invoices/{public-id}/details
     *
     * @return string
     */
    public function getDashboardPath()
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
    public function getPdfFilename()
    {
        return self::PDF_PREFIX . $this->getId() . '_' . time();
    }

    public function getPdfDisplayName()
    {
        //
        // Expected format:
        // Invoice <Reciept/Invoice ID> from <Company> (<Paid/Unpaid>).pdf
        //

        $receipt = $this->getReceiptElsePublicId();
        $from    = $this->merchant->getBillingLabelElseName();
        $status  = $this->hasBeenPaid() ? 'Paid' : 'Unpaid';

        return sanitizeFilename("Invoice $receipt from $from ($status)");
    }

    // -------------------------------------- End Getters --------------------------------------


    // -------------------------------------- Setters --------------------------------------

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

        $billingAddress = $repo->address->fetchPrimaryAddressOfEntityOfType($customer, Address\Type::BILLING_ADDRESS);

        if ($billingAddress !== null)
        {
            $this->setCustomerBillingAddrId($billingAddress->getId());
        }
    }

    public function setCustomerName($customerName)
    {
        $this->setAttribute(self::CUSTOMER_NAME, $customerName);
    }

    public function setCustomerBillingAddrId($customerBillingAddressId)
    {
        $this->setAttribute(self::CUSTOMER_BILLING_ADDR_ID, $customerBillingAddressId);
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

    public function setStatus($status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);

        // Sets corresponding timestamps as per new status
        if (in_array($status, Status::$timestampedStatuses, true))
        {
            $timestampKey = $status . '_at';
            $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

            $this->setAttribute($timestampKey, $currentTime);
        }
    }

    public function setSubStatus($subStatus)
    {
        Status::checkSubStatus($subStatus);

        $this->setAttribute(self::SUB_STATUS, $subStatus);
    }

    public function setShortUrl($shortUrl)
    {
        $this->setAttribute(self::SHORT_URL, $shortUrl);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setBillingStart($billingStart)
    {
        $this->setAttribute(self::BILLING_START, $billingStart);
    }

    public function setBillingEnd($billingEnd)
    {
        $this->setAttribute(self::BILLING_END, $billingEnd);
    }

    /**
     * @deprecated
     *
     * Sets expire_by's default value at the time of issue of invoices.
     * Gets called from Generator->issueInvoice() method.
     */
    public function setDefaultExpireByIfNotAlreadySet()
    {
        if ($this->getExpireBy() !== null)
        {
            return;
        }

        $expireBy = Carbon::now('Asia/Kolkata')
                          ->addDays(self::DEFAULT_EXPIRY_DAYS)
                          ->timestamp;

        $this->setAttribute(self::EXPIRE_BY, $expireBy);
    }

    // -------------------------------------- End Setters --------------------------------------

    // -------------------------------------- Accessors --------------------------------------

    /**
     * @deprecated Replaced with setPublicCustomerAttribute method.
     *
     * @return array
     */
    protected function getCustomerDetailsAttribute()
    {
        return [
            self::CUSTOMER_NAME            => $this->getAttribute(self::CUSTOMER_NAME),
            self::CUSTOMER_EMAIL           => $this->getAttribute(self::CUSTOMER_EMAIL),
            self::CUSTOMER_CONTACT         => $this->getAttribute(self::CUSTOMER_CONTACT),
            self::CUSTOMER_ADDRESS         => null,
        ];
    }

    /**
     * TODO: Remove this post expand pr is merged. Also remove from $appends.
     *
     * @return Base\PublicCollection
     */
    protected function getLineItemsAttribute()
    {
        $lineItems = $this->lineItems()->getResults()->toArrayPublicEmbedded();

        return $lineItems;
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

    // -------------------------------------- End Accessors --------------------------------------

    // -------------------------------------- Public Setters --------------------------------------

    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    protected function setPublicCustomerAttribute(array & $array)
    {
        $array[self::CUSTOMER] = [
            Customer\Entity::NAME    => $this->getAttribute(self::CUSTOMER_NAME),
            Customer\Entity::EMAIL   => $this->getAttribute(self::CUSTOMER_EMAIL),
            Customer\Entity::CONTACT => $this->getAttribute(self::CUSTOMER_CONTACT),
        ];

        if ($this->hasCustomerBillingAddress() === true)
        {
            $billingAddress = $this->customerBillingAddress->toArrayPublic();

            $array[self::CUSTOMER][Customer\Entity::BILLING_ADDRESS] = $billingAddress;
        }
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

    // -------------------------------------- End Public Setters --------------------------------------

    // -------------------------------------- Generators --------------------------------------

    public function generateDate($input)
    {
        // If DATE is not sent in input, set it to now
        // If DATE is sent, even as null use that only(so not using isset)
        if (array_key_exists(Entity::DATE, $input) === false)
        {
            $now = Carbon::now('Asia/Kolkata')->timestamp;

            $this->setAttribute(self::DATE, $now);
        }
    }

    public function generateEmailStatus($input)
    {
        $this->setAttribute(self::EMAIL_STATUS, NotifyStatus::PENDING);

        // Should not use `empty` because the value can be 0
        if ((isset($input[self::EMAIL_NOTIFY]) === true) and
            ($input[self::EMAIL_NOTIFY] === '0'))
        {
            $this->setAttribute(self::EMAIL_STATUS, null);
        }
    }

    public function generateSmsStatus($input)
    {
        $this->setAttribute(self::SMS_STATUS, NotifyStatus::PENDING);

        // Should not use `empty` because the value can be 0
        if ((isset($input[self::SMS_NOTIFY]) === true) and
            ($input[self::SMS_NOTIFY] === '0'))
        {
            $this->setAttribute(self::SMS_STATUS, null);
        }
    }

    public function generateDueBy($input)
    {
        if (empty($input[self::DUE_BY]) === false)
        {
            $dueBy = $input[self::DUE_BY];
        }
        else
        {
            $dueBy = Carbon::now('Asia/Kolkata')->addDays(self::DEFAULT_DUE_DAYS)->timestamp;
        }

        $this->setAttribute(self::DUE_BY, $dueBy);
    }

    public function generateScheduledAt($input)
    {
        if (empty($input[self::SCHEDULED_AT]) === false)
        {
            $scheduledAt = $input[self::SCHEDULED_AT];
        }
        else
        {
            $scheduledAt = Carbon::now('Asia/Kolkata')->timestamp;
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
    public function generateStatus($input)
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

    // public function generateDiscount($input)
    // {
    //     if (isset($input[self::DISCOUNT_FLAT]))
    //     {
    //         $this->useDiscountFlatToSetDiscount($input);
    //     }
    //
    //     if (isset($input[self::DISCOUNT_PERCENT]))
    //     {
    //         $this->useDiscountPercentToSetDiscount($input);
    //     }
    // }

    // protected function useDiscountPercentToSetDiscount($input)
    // {
    //     $discountPercent = $input[self::DISCOUNT_PERCENT]/100;
    //
    //     $totalAmount = $discountableAmount = $input[self::TOTAL_AMOUNT];
    //
    //     if (isset($input[self::TOTAL_TAX]) === true)
    //     {
    //         $discountableAmount = $totalAmount - $input[self::TOTAL_TAX];
    //     }
    //
    //     $discount = $discountableAmount * $discountPercent;
    //
    //     $this->setAttribute(self::DISCOUNT, round($discount));
    // }

    // protected function useDiscountFlatToSetDiscount($input)
    // {
    //     $discount = $input[self::DISCOUNT_FLAT];
    //
    //     $this->setAttribute(self::DISCOUNT, $discount);
    // }

    // -------------------------------------- End Generators --------------------------------------

    // -------------------------------------- Relations --------------------------------------

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

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function customerBillingAddress()
    {
        return $this->belongsTo('RZP\Models\Address\Entity', 'customer_billing_addr_id');
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
    public function pdf()
    {
        return $this->files()
                    ->where(FileStore\Entity::TYPE, '=', FileStore\Type::INVOICE_PDF)
                    ->latest()
                    ->first();
    }

    // -------------------------------------- End Relations --------------------------------------

    // -------------------------------------- Query scopes --------------------------------------

    public function scopeStatus($query, $status)
    {
        return $query->where(Entity::STATUS, '=', $status);
    }

    // -------------------------------------- Query scopes section ends --------------------------------------

    public function getValidOperations()
    {
        return $this->validOperations;
    }
}
