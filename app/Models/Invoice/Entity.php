<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ORDER_ID              = 'order_id';
    const CUSTOMER_ID           = 'customer_id';
    const CUSTOMER_NAME         = 'customer_name';
    const CUSTOMER_EMAIL        = 'customer_email';
    // TODO: Use an address ID here instead.
    const CUSTOMER_ADDRESS      = 'customer_address';
    const CUSTOMER_CONTACT      = 'customer_contact';
    const STATUS                = 'status';
    const DUE_BY                = 'due_by';
    // const SHIPPING              = 'shipping';
    // const DISCOUNT              = 'discount';
    const EMAIL_STATUS          = 'email_status';
    const SMS_STATUS            = 'sms_status';

    // Present only in request
    const DUE_IN                = 'due_in';
    const ITEMS                 = 'items';
    // const DISCOUNT_FLAT         = 'discount_flat';
    // const DISCOUNT_PERCENT      = 'discount_percent';
    const EMAIL_NOTIFY          = 'email_notify';
    const SMS_NOTIFY            = 'sms_notify';
    const TOTAL_AMOUNT          = 'total_amount';
    const CURRENCY              = 'currency';

    const CUSTOMER_DETAILS      = 'customer_details';
    const ITEMS_DETAILS         = 'items_details';

    // const TOTAL_TAX             = 'total_tax';

    const DEFAULT_DUE_DAYS      = 60;

    protected static $sign = 'inv';

    protected $entity = 'invoice';

    protected $table = Table::INVOICE;

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::STATUS            => Status::CREATED,
        // self::ADJUSTMENT        => 0,
        // self::SHIPPING          => 0,
        self::EMAIL_STATUS      => Status::PENDING,
        self::SMS_STATUS        => Status::PENDING,
    ];

    // Generates fields to be filled in the DB.
    // No validation performed on these fields.
    protected static $generators = [
        // self::DISCOUNT,
        self::DUE_BY,
        self::EMAIL_STATUS,
        self::SMS_STATUS,
    ];

    // Fields that can be inserted by ->fill() directly
    // This array should also include the fields mentioned in the generator.
    protected $fillable = [
        self::DUE_BY,
        self::EMAIL_STATUS,
        self::SMS_STATUS,
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
        self::CUSTOMER_DETAILS,
        self::ITEMS_DETAILS,
        // self::ADJUSTMENT,
        // self::SHIPPING,
        // self::DISCOUNT,
        self::SMS_STATUS,
        self::EMAIL_STATUS,
        self::CURRENCY,
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    // Fields to be exposed to the client
    protected $public = [
        self::ID,
        self::ENTITY,
        // TODO: Customer ID is separate because the details of customer id
        // can change later. The invoice details will be in customer_details.
        // The customer_id at the time of generation of the invoice for the
        // given customer details would be this customer id here.
        self::CUSTOMER_ID,
        self::CUSTOMER_DETAILS,
        self::ITEMS_DETAILS,
        self::STATUS,
        self::CREATED_AT,
        self::DUE_BY,
        // self::SHIPPING,
        // self::ADJUSTMENT,
        // self::DISCOUNT,
        self::SMS_STATUS,
        self::EMAIL_STATUS,
        self::CURRENCY,
    ];

    // Fields to be added while retrieving the entity
    protected $appends = [
        self::PUBLIC_ID, self::ENTITY, self::CUSTOMER_DETAILS,
    ];

    // The functions for these fields will be called only
    // via toArrayPublic()
    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ITEMS_DETAILS,
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

    public function setItemsDetails($items)
    {
        $this->setAttribute(self::ITEMS_DETAILS, $items);
    }

    public function setSmsStatus(Status $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::SMS_STATUS, $status);
    }

    public function setEmailStatus(Status $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::EMAIL_STATUS, $status);
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

    protected function setPublicItemsDetailsAttribute(array & $array)
    {
        // TODO: This will output a collection of items directly.
        // Should we instead iterate through each item in the collection
        // and return back an array of items instead of an entity collection?
        $array[self::ITEMS_DETAILS] = $this->items()->getResults()->toArrayPublic();
    }

    // -------------------------------------- End Public Setters --------------------------------------

    // -------------------------------------- Generators --------------------------------------

    public function generateEmailStatus($input)
    {
        if ((empty($input[self::EMAIL_NOTIFY]) === false) and
            ($input[self::EMAIL_NOTIFY] === false))
        {
            $this->setAttribute(self::EMAIL_STATUS, null);
        }
    }

    public function generateSmsStatus($input)
    {
        if ((empty($input[self::SMS_NOTIFY]) === false) and
            ($input[self::SMS_NOTIFY] === false))
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

    public function items()
    {
        return $this->belongsToMany('RZP\Models\Item\Entity', Table::INVOICE_ITEM)
                    ->withTimestamps();
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

    public function scopeCreatedAtLessThan($query, $ts)
    {
        return $query->where(Entity::CREATED_AT, '<', $ts);
    }

    public function scopeMerchantId($query, $merchantId)
    {
        return $query->where(self::MERCHANT_ID,'=',$merchantId);
    }

// -------------------------------------- Query scopes section ends --------------------------------------
}