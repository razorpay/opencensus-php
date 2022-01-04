<?php

namespace RZP\Models\Internal;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Currency\Currency;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID               = 'merchant_id';
    const TRANSACTION_ID            = 'transaction_id';
    const UTR                       = 'utr';
    const TYPE                      = 'type';
    const AMOUNT                    = 'amount';
    const BASE_AMOUNT               = 'base_amount';
    const CURRENCY                  = 'currency';
    const REMARKS                   = 'remarks';
    const TRANSACTION_DATE          = 'transaction_date';
    const RECONCILED_AT             = 'reconciled_at';
    const STATUS                    = 'status';

    protected static $sign = 'intr';

    protected $entity = 'internal';

    protected $fillable = [
        self::ID,
        self::TYPE,
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::CURRENCY,
        self::UTR,
        self::REMARKS,
        self::TRANSACTION_DATE,
        self::MERCHANT_ID,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TRANSACTION_ID,
        self::UTR,
        self::REMARKS,
        self::TYPE,
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::TRANSACTION_DATE,
        self::RECONCILED_AT,
        self::STATUS,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::TRANSACTION_ID,
        self::UTR,
        self::TYPE,
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::CURRENCY,
        self::REMARKS,
        self::ENTITY,
        self::TRANSACTION_DATE,
        self::STATUS,
    ];

    protected $defaults = [
        self::CURRENCY => Currency::INR,
    ];

    protected $casts = [
        self::AMOUNT      => 'int',
        self::BASE_AMOUNT => 'int',
    ];

    protected static $generators = [
        self::ID,
    ];

    //
    // Relations with other entities
    //

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }
}
