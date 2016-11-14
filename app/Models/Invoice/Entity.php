<?php

namespace RZP\Models\Invoice;

use App;
use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Customer;
use RZP\Models\Order;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    // ------------------ Entity Keys --------------------------------

    const ORDER_ID              = 'order_id';
    const CUSTOMER_ID           = 'customer_id';
    const CUSTOMER_NAME         = 'customer_name';
    const CUSTOMER_EMAIL        = 'customer_email';
    const CUSTOMER_ADDRESS      = 'customer_address';
    const CUSTOMER_CONTACT      = 'customer_contact';
    // Invoice status
    const STATUS                = 'status';
    const DUE_BY                = 'due_by';
    const SCHEDULED_AT          = 'scheduled_at';
    const EMAIL_STATUS          = 'email_status';
    const SMS_STATUS            = 'sms_status';
    const DATE                  = 'date';
    const TERMS                 = 'terms';
    const NOTES                 = 'notes';
    const SHORT_URL             = 'short_url';
    const VIEW_LESS             = 'view_less';

    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';

    // ---------------------- Input Keys -------------------------------------

    // Input key for sending line item details
    const LINE_ITEMS            = 'line_items';
    // Input key for sending customer details
    const CUSTOMER              = 'customer';
    // Input key on whether to notify the customer by email
    const EMAIL_NOTIFY          = 'email_notify';
    // Input key on whether to notify the customer by sms
    const SMS_NOTIFY            = 'sms_notify';
    // Input key to send the expiry date of the invoice
    const DUE_IN                = 'due_in';
    // Input key to send the scheduling time for notifying the customer
    const SCHEDULED_IN          = 'scheduled_in';
    // Input key to send whether the invoice should be created in draft state
    const DRAFT                 = 'draft';

    // ---------------------- Input Keys End -------------------------------------

    // ------------------------- Output Keys --------------------------------------

    const CUSTOMER_DETAILS      = 'customer_details';
    const LINE_ITEMS_DETAILS    = 'line_items_details';

    // ------------------------ Output Keys End -----------------------------------

    const EMAIL                 = 'email';
    const SMS                   = 'sms';
    const ITEMS                 = 'items';

    const DEFAULT_DUE_DAYS      = 60;

    protected static $sign = 'inv';

    protected $entity = 'invoice';

    protected $table = Table::INVOICE;

    protected $generateIdOnCreate = true;

    protected $defaults = [
        // self::STATUS            => null,
        // self::ADJUSTMENT        => 0,
        // self::SHIPPING          => 0,
        self::NOTES             => [],
        self::SHORT_URL         => null,
        self::VIEW_LESS         => true,
    ];

    // Generates fields to be filled in the DB.
    // No validation performed on these fields.
    protected static $generators = [
        // self::DISCOUNT,
        self::DUE_BY,
        self::SCHEDULED_AT,
        self::EMAIL_STATUS,
        self::SMS_STATUS,
    ];

    // Fields that can be inserted by ->fill() directly
    // This array should also include the fields mentioned in the generator.
    protected $fillable = [
        // self::DUE_BY,
        // self::SCHEDULED_AT,
        self::EMAIL_STATUS,
        self::SMS_STATUS,
        self::DATE,
        // self::TERMS,
        self::NOTES,
        self::VIEW_LESS,
        // self::ADJUSTMENT,
        // self::SHIPPING,
        // self::DISCOUNT,
    ];

    // Fields to be exposed by the entity in general
    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::STATUS,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
        self::ORDER_ID,
        // self::CUSTOMER_EMAIL,
        // self::CUSTOMER_CONTACT,
        // self::CUSTOMER_NAME,
        // self::CUSTOMER_ADDRESS,
        self::DUE_BY,
        self::SCHEDULED_AT,
        self::CUSTOMER_DETAILS,
        self::LINE_ITEMS_DETAILS,
        self::SMS_STATUS,
        self::EMAIL_STATUS,
        self::MERCHANT_ID,
        self::DATE,
        self::TERMS,
        self::NOTES,
        self::SHORT_URL,
        self::VIEW_LESS,
        self::AMOUNT,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    // Fields to be exposed to the client
    protected $public = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::ORDER_ID,
        self::CUSTOMER_DETAILS,
        self::LINE_ITEMS_DETAILS,
        self::STATUS,
        // self::DUE_BY,
        // self::SCHEDULED_AT,
        self::SMS_STATUS,
        self::EMAIL_STATUS,
        self::DATE,
        // self::TERMS,
        self::AMOUNT,
        self::NOTES,
        self::SHORT_URL,
        self::VIEW_LESS,
        // self::TOTAL_AMOUNT,
        self::CREATED_AT,
    ];

    // Fields to be added while retrieving the entity
    protected $appends = [
        self::PUBLIC_ID,
        self::ENTITY,
        self::CUSTOMER_DETAILS,
    ];

    // The functions for these fields will be called only
    // via toArrayPublic()
    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::ORDER_ID,
        self::LINE_ITEMS_DETAILS,
    ];

    protected $casts = [
        self::VIEW_LESS => 'bool',
    ];

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

    public function getPaymentId()
    {
        $repo = App::getFacadeRoot()['repo'];

        $payment = $repo->payment->getCapturedPaymentForOrder($this->getOrderId());

        if ($payment !== null)
        {
            return $payment->getId();
        }

        return null;
    }

    // -------------------------------------- End Getters --------------------------------------


    // -------------------------------------- Setters --------------------------------------

    public function setCustomerName($customerName)
    {
        $this->setAttribute(self::CUSTOMER_NAME, $customerName);
    }

    public function setCustomerAddress($customerAddress)
    {
        $this->setAttribute(self::CUSTOMER_ADDRESS, $customerAddress);
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
        NotifyStatus::checkStatus($status);

        $this->setAttribute(self::SMS_STATUS, $status);
    }

    public function setEmailStatus($status)
    {
        NotifyStatus::checkStatus($status);

        $this->setAttribute(self::EMAIL_STATUS, $status);
    }

    public function setStatus($status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setShortUrl($shortUrl)
    {
        $this->setAttribute(self::SHORT_URL, $shortUrl);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    // -------------------------------------- End Setters --------------------------------------

    // -------------------------------------- Accessors --------------------------------------

    protected function getCustomerDetailsAttribute()
    {
        return [
            self::CUSTOMER_NAME     => $this->attributes[self::CUSTOMER_NAME],
            self::CUSTOMER_EMAIL    => $this->attributes[self::CUSTOMER_EMAIL],
            self::CUSTOMER_CONTACT  => $this->attributes[self::CUSTOMER_CONTACT],
            self::CUSTOMER_ADDRESS  => $this->attributes[self::CUSTOMER_ADDRESS],
        ];
    }

    // -------------------------------------- End Accessors --------------------------------------

    // -------------------------------------- Public Setters --------------------------------------

    protected function setPublicLineItemsDetailsAttribute(array & $array)
    {
        $array[self::LINE_ITEMS_DETAILS] = $this->lineItems()->getResults()->toArrayPublicEmbedded();
    }

    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedId($customerId);
    }

    protected function setPublicOrderIdAttribute(array & $array)
    {
        $orderId = $this->getAttribute(self::ORDER_ID);

        $array[self::ORDER_ID] = Order\Entity::getSignedId($orderId);
    }

    // -------------------------------------- End Public Setters --------------------------------------

    // -------------------------------------- Generators --------------------------------------

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
        $dueDays = self::DEFAULT_DUE_DAYS;

        if (empty($input[self::DUE_IN]) === false)
        {
            $dueDays = $input[self::DUE_IN];
        }

        $dueBy = Carbon::now('Asia/Kolkata')->addDays($dueDays)->timestamp;

        $this->setAttribute(self::DUE_BY, $dueBy);
    }

    public function generateScheduledAt($input)
    {
        $scheduledAt = Carbon::now('Asia/Kolkata');

        if (empty($input[self::SCHEDULED_IN]) === false)
        {
            $scheduledAt = $scheduledAt->addDays($input[self::SCHEDULED_IN]);
        }

        $this->setAttribute(self::SCHEDULED_AT, $scheduledAt->timestamp);
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
        return $this->hasMany('RZP\Models\LineItem\Entity');
    }

    // TODO: We don't need to really store merchant in this entity. Invoice is
    // already associated with an order, which in turn is associated with
    // a  merchant. But, I don't want to tightly couple orders with invoices,
    // wherever possible. Orders was not initially meant for this kind of use-case.
    // The more we couple orders to other entities, its purpose gets lost and the
    // complexity increases exponentially with every new feature using orders.
    //
    // Also, querying becomes easier by storing the merchant in the invoices table itself.
    // Lesser complexity.
    // Thoughts please.
    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // -------------------------------------- End Relations --------------------------------------

    // -------------------------------------- Query scopes --------------------------------------

    public function scopeStatus($query, $status)
    {
        return $query->where(Entity::STATUS, '=', $status);
    }

// -------------------------------------- Query scopes section ends --------------------------------------
}
