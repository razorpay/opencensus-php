<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                    = 'id';
    const ORDER_ID              = 'order_id';
    // TODO: Should we store this here? Orders entity already
    // has a customer_id field. We can use that.
    const CUSTOMER_ID           = 'customer_id';
    const CUSTOMER_NAME         = 'customer_name';
    const CUSTOMER_EMAIL        = 'customer_email';
    // TODO: Should we use an address ID here instead?
    const CUSTOMER_ADDRESS      = 'customer_address';
    const CUSTOMER_PHONE        = 'customer_phone';
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
        self::EMAIL_NOTIFY      => true,
        self::SMS_NOTIFY        => true,
        self::CURRENCY          => 'INR',
    ];

    protected $fillable = [
        self::DUE_BY,
        // self::ADJUSTMENT,
        // self::SHIPPING,
        // self::DISCOUNT,
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::STATUS,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
        self::ORDER_ID,
        self::CUSTOMER_EMAIL,
        self::CUSTOMER_PHONE,
        self::CUSTOMER_NAME,
        self::CUSTOMER_ADDRESS,
        self::DUE_BY,
        // self::ADJUSTMENT,
        // self::SHIPPING,
        // self::DISCOUNT,
        self::SMS_STATUS,
        self::EMAIL_STATUS,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::CUSTOMER_ADDRESS,
        self::CUSTOMER_EMAIL,
        self::CUSTOMER_NAME,
        self::CUSTOMER_PHONE,
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

    protected static $generators = [
        // self::DISCOUNT,
        self::DUE_BY,
    ];

    protected $dates = array(self::DUE_BY);

    //------------------Generators--------------------------------------
    
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
    
    //--------------------- End Generators ------------------------------------ 
    
    //-------------------------- Relations ------------------------------------

    public function order()
    {
        return $this->belongsTo('RZP\Models\Order\Entity');
    }
    
    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }
    
    //-------------------------- End Relations --------------------------------
}