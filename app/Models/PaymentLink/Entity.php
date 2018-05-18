<?php

namespace RZP\Models\PaymentLink;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    const RECEIPT       = 'receipt';
    const MERCHANT_ID   = 'merchant_id';
    const AMOUNT        = 'amount';
    const CURRENCY      = 'currency';
    const EXPIRE_BY     = 'expire_by';
    const TIMES_PAYABLE = 'times_payable';
    const TIMES_PAID    = 'times_paid';
    const TOTAL_AMOUNT  = 'total_amount';
    const STATUS        = 'status';
    const STATUS_REASON = 'status_reason';
    const SHORT_URL     = 'short_url';
    const USER_ID       = 'user_id';
    const TITLE         = 'title';
    const DESCRIPTION   = 'description';
    const NOTES         = 'notes';

    protected static $sign = 'pl';

    protected $entity = 'payment_link';

    protected $fillable = [
        self::RECEIPT,
        self::AMOUNT,
        self::CURRENCY,
        self::EXPIRE_BY,
        self::TIMES_PAYABLE,
        self::TITLE,
        self::DESCRIPTION,
        self::NOTES,
    ];

    protected $visible = [
        self::ID,
        self::RECEIPT,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::EXPIRE_BY,
        self::TIMES_PAYABLE,
        self::TIMES_PAID,
        self::TOTAL_AMOUNT,
        self::STATUS,
        self::STATUS_REASON,
        self::SHORT_URL,
        self::USER_ID,
        self::TITLE,
        self::DESCRIPTION,
        self::NOTES,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::RECEIPT,
        self::AMOUNT,
        self::CURRENCY,
        self::EXPIRE_BY,
        self::TIMES_PAYABLE,
        self::TIMES_PAID,
        self::TOTAL_AMOUNT,
        self::STATUS,
        self::STATUS_REASON,
        self::SHORT_URL,
        self::USER_ID,
        self::TITLE,
        self::DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $hosted = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::SHORT_URL,
        self::TITLE,
        self::DESCRIPTION,
    ];

    protected $casts = [
        self::AMOUNT        => 'int',
        self::TIMES_PAYABLE => 'int',
        self::TIMES_PAID    => 'int',
        self::TOTAL_AMOUNT  => 'int',
    ];

    protected $dates = [
        self::EXPIRE_BY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $defaults = [
        self::EXPIRE_BY     => null,
        self::TIMES_PAYABLE => null,
        self::TIMES_PAID    => 0,
        self::TOTAL_AMOUNT  => 0,
        self::STATUS        => Status::ACTIVE,
        self::STATUS_REASON => null,
        self::USER_ID       => null,
        self::TITLE         => null,
        self::DESCRIPTION   => null,
        self::NOTES         => null,
    ];

    // -------------------------------------- Relations -------------------------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment\Entity::class)
                    ->orderBy(Payment\Entity::CREATED_AT, 'desc');
    }
}
