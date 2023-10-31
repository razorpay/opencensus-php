<?php

namespace RZP\Models\PaymentLink\PaymentPageRecord;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Constants\Entity as Constants;
use RZP\Models\Base;
use RZP\Models\PaymentLink;

/**
 * @property PaymentLink\Entity $paymentLink
 */
class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $entity = Constants::PAYMENT_PAGE_RECORD;

    const PAYMENT_LINK_ID       = 'payment_link_id';
    const BATCH_ID              = 'batch_id';
    const PRIMARY_REF_ID        = 'pri__ref__id';
    const PRIMARY_REFERENCE_ID  = 'primary_reference_id';
    const PRIMARY_REF_ID_REPO   = 'primary_ref_id';
    const SECONDARY_1           = 'sec__ref__id_1';
    const SECONDARY_2           = 'sec__ref__id_2';
    const SECONDARY_3           = 'sec__ref__id_3';
    const SECONDARY_4           = 'sec__ref__id_4';
    const SECONDARY_5           = 'sec__ref__id_5';
    const STATUS                = 'status';
    const AMOUNT                = 'amount';
    const TOTAL_AMOUNT          = 'total_amount';
    const EMAIL                 = 'email';
    const EMAILS                = 'emails';
    const PHONE                 = 'phone';
    const CONTACT               = 'contact';
    const CONTACTS              = 'contacts';
    const MERCHANT_ID           = 'merchant_id';
    const OTHER_DETAILS         = 'other_details';
    const PATTERN               = 'pattern';
    const REQUIRED              = 'required';
    const SMS_NOTIFY            = 'sms_notify';
    const EMAIL_NOTIFY          = 'email_notify';
    const MANDATORY             = 'mandatory';
    const CUSTOM_FIELD_SCHEMA   = 'custom_field_schema';
    const TOTAL_PENDING_PAYMENTS= 'total_pending_payments';
    const TOTAL_PENDING_REVENUE = 'total_pending_revenue';
    const TOTAL_PENDING_LATE_FEE ='total_pending_late_fee';

    // get batches params
    const SKIP                  = 'skip';
    const COUNT                 = 'count';
    const ALL_BATCHES           = 'all_batches';

    const SEC_REF_ID_PREFIX = 'sec__ref__id';

    // fixed titles for email and contact
    const EMAIL_TITLE = "Email";
    const PHONE_TITLE = "Phone";

    // late Fee fields
    const LATE_FEE_CONFIG = 'late_fee_config';
    const LATE_FEE_DUE_DATE = 'late__fee__due__date_1';
    const LATE_FEE_RATE_1 = 'late_fee_rate_1';
    const LATE_FEE_TYPE = 'late_fee_type';
    const LATE_FEE_ORDER = 'late_fee_order';
    const FLAT_LATE_FEE = 'flat_late_fee';
    const PER_DAY_LATE_FEE = 'per_day_late_fee';
    const LATE_FEE_PRICES = 'late_fee_prices';

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_LINK_ID,
        self::BATCH_ID,
        self::PRIMARY_REFERENCE_ID,
        self::AMOUNT,
        self::EMAIL,
        self::CONTACT,
        self::MERCHANT_ID,
        self::STATUS,
        self::OTHER_DETAILS,
        self::TOTAL_AMOUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::CUSTOM_FIELD_SCHEMA,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_LINK_ID,
        self::BATCH_ID,
        self::PRIMARY_REFERENCE_ID,
        self::AMOUNT,
        self::EMAIL,
        self::CONTACT,
        self::MERCHANT_ID,
        self::STATUS,
        self::OTHER_DETAILS,
        self::TOTAL_AMOUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::CUSTOM_FIELD_SCHEMA,
    ];

    protected $fillable = [
        self::ID,
        self::PAYMENT_LINK_ID,
        self::BATCH_ID,
        self::PRIMARY_REFERENCE_ID,
        self::AMOUNT,
        self::EMAIL,
        self::CONTACT,
        self::MERCHANT_ID,
        self::STATUS,
        self::OTHER_DETAILS,
        self::TOTAL_AMOUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::CUSTOM_FIELD_SCHEMA,
    ];

    protected $defaults = [
        self::TOTAL_AMOUNT => 0
    ];

    public static $secondary_ref_ids = [
        self::SECONDARY_1,
        self::SECONDARY_2,
        self::SECONDARY_3,
        self::SECONDARY_4,
        self::SECONDARY_5,
    ];

    public function paymentLink()
    {
        return $this->belongsTo(PaymentLink\Entity::class);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function setStatus(string $status)
    {
        Status::validateStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setTotalAmount(int $totalAmount)
    {
        $this->setAttribute(self::TOTAL_AMOUNT, $totalAmount);
    }

    public static function isSecondaryRefId(String $name)
    {
        return strpos($name, self::SEC_REF_ID_PREFIX) === 0;
    }

    public static function isRefId(String $name)
    {
        return ($name === self::PRIMARY_REF_ID) or (self::isSecondaryRefId($name));
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }
}
