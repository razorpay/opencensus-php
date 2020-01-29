<?php

namespace RZP\Models\Settlement\Bucket;

use RZP\Models\Base;
use RZP\Models\Merchant\Balance;

class Entity extends Base\PublicEntity
{
    const ID                 = 'id';
    const MERCHANT_ID        = 'merchant_id';
    const BALANCE_TYPE       = 'balance_type';
    const BUCKET_TIMESTAMP   = 'bucket_timestamp';
    const COMPLETED          = 'completed';

    protected $entity = 'settlement_bucket';

    protected $fillable = [
        self::MERCHANT_ID,
        self::BALANCE_TYPE,
        self::BUCKET_TIMESTAMP,
        self::COMPLETED,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::BALANCE_TYPE,
        self::BUCKET_TIMESTAMP,
        self::COMPLETED,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::BALANCE_TYPE,
        self::BUCKET_TIMESTAMP,
        self::COMPLETED,
    ];

    protected $defaults = [
        self::COMPLETED    => 0,
        self::BALANCE_TYPE => Balance\Type::PRIMARY,
    ];

    public function getBucketTimestamp(): int
    {
        return $this->getAttribute(self::BUCKET_TIMESTAMP);
    }
}
