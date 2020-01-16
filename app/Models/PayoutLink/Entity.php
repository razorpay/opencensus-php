<?php

namespace RZP\Models\PayoutLink;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\FundAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Base\Traits\HasBalance;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Merchant\Entity        $merchant
 * @property User\Entity            $user
 * @property FundAccount\Entity     $fundAccount
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use HasBalance;
    use SoftDeletes;

    protected $table = Table::PAYOUT_LINK;

    // Payout Link Columns
    const ID                   = 'id';
    const CONTACT_ID           = 'contact_id';
    const CONTACT_NAME         = 'contact_name';
    const CONTACT_PHONE_NUMBER = 'contact_phone_number';
    const CONTACT_EMAIL        = 'contact_email';
    const FUND_ACCOUNT_ID      = 'fund_account_id';
    const BALANCE_ID           = 'balance_id';
    const SHORT_URL            = 'short_url';
    const MERCHANT_ID          = 'merchant_id';
    const USER_ID              = 'user_id';
    const BATCH_ID             = 'batch_id';
    const IDEMPOTENCY_KEY      = 'idempotency_key';
    const STATUS               = 'status';
    const AMOUNT               = 'amount';
    const NOTES                = 'notes';
    // This purpose will be used in creating payouts. So validation will be same as that on Payout Purpose
    const PURPOSE              = 'purpose';
    // This description is text that the merchant wants to add while creating payout-link id.
    // This will be shown to the customer while entering bank account details
    const DESCRIPTION          = 'description';
    const RECEIPT              = 'receipt';
    const CURRENCY             = 'currency';
    const CANCELLED_AT         = 'cancelled_at';
    const CREATED_AT           = 'created_at';
    const UPDATED_AT           = 'updated_at';

    // Strings used in Core / Validators
    const CONTEXT              = 'context';
    const OTP                  = 'otp';
    const TOKEN                = 'token';
    const IMPS                 = 'IMPS';
    const NEFT                 = 'NEFT';
    const UPI                  = 'UPI';
    const ACCOUNT_TYPE         = 'account_type';
    const VPA                  = 'vpa';
    const BANK_ACCOUNT         = 'bank_account';
    const ACCOUNT_NUMBER       = 'account_number';
    const CONTACT              = 'contact';
    const NAME                 = 'name';
    const EMAIL                = 'email';
    const PHONE_NUMBER         = 'contact';

    const MAX_PAYOUT_LIMIT     = Payout\Entity::MAX_PAYOUT_LIMIT;
    const MERCHANT_NAME        = 'merchant_name';
    const PAYOUT_PURPOSE       = 'payout_purpose';
    const CUSTOMER_NAME        = 'customer_name';

    protected $generateIdOnCreate = true;

    protected $entity = 'payout_link';

    protected static $sign = 'poutlk';

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::PURPOSE,
        self::RECEIPT,
        self::NOTES,
        self::BALANCE_ID,
        self::CONTACT_NAME,
        self::CONTACT_EMAIL,
        self::CONTACT_PHONE_NUMBER,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::USER_ID,
        self::PURPOSE,
        self::CONTACT_ID,
        self::CONTACT_NAME,
        self::CONTACT_EMAIL,
        self::CONTACT_PHONE_NUMBER,
        self::FUND_ACCOUNT_ID,
        self::SHORT_URL,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::RECEIPT,
        self::NOTES,
        self::SHORT_URL,
        self::CANCELLED_AT,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CONTACT_ID,
        self::CONTACT,
        self::FUND_ACCOUNT_ID,
        self::PURPOSE,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::RECEIPT,
        self::NOTES,
        self::SHORT_URL,
        self::CANCELLED_AT,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::STATUS,
        self::ENTITY,
        self::ID,
        self::CONTACT_ID,
        self::FUND_ACCOUNT_ID,
        self::CONTACT
    ];

    protected $hosted = [
        self::ID,
        self::STATUS,
        self::AMOUNT,
        self::PURPOSE,
        self::CURRENCY,
        self::DESCRIPTION,
        self::RECEIPT,
        self::CANCELLED_AT
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::CANCELLED_AT
    ];

    protected $amounts = [
      self::AMOUNT
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $defaults = [
        self::CONTACT_NAME         => null,
        self::CONTACT_EMAIL        => null,
        self::CONTACT_PHONE_NUMBER => null,
        self::FUND_ACCOUNT_ID      => null,
        self::SHORT_URL            => null,
        self::USER_ID              => null,
        self::CURRENCY             => Currency::INR,
        self::DESCRIPTION          => null,
        self::RECEIPT              => null,
        self::NOTES                => [],
        self::CANCELLED_AT         => null,
    ];
}
