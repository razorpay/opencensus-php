<?php

namespace RZP\Models\Settlement\Bucket;

use RZP\Models\Base;

class Entity extends Base\Entity
{
    const ID                 = 'id';
    const MERCHANT_ID        = 'merchant_id';
    const BUCKET_TIMESTAMP   = 'bucket_timestamp';
    const COMPLETED          = 'completed';

    protected $entity = 'settlement_bucket';

    protected $fillable = [
        self::MERCHANT_ID,
        self::BUCKET_TIMESTAMP,
        self::COMPLETED,
    ];

    protected $visible = [
        self::MERCHANT_ID,
        self::BUCKET_TIMESTAMP,
        self::COMPLETED,
    ];

    protected $public = [
        self::MERCHANT_ID,
        self::BUCKET_TIMESTAMP,
        self::COMPLETED,
    ];

    protected $defaults = [
        self::COMPLETED => 0,
    ];
}
