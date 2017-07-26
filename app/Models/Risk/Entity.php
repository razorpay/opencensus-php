<?php

namespace RZP\Models\Risk;

use RZP\Models\Base;
use RZP\Models\Base\Traits\RevisionableTrait;

class Entity extends Base\PublicEntity
{
    use RevisionableTrait;

    const PAYMENT_ID    = 'payment_id';
    const MERCHANT_ID   = 'merchant_id';
    const FRAUD_TYPE    = 'fraud_type';
    const SOURCE        = 'source';
    const MAXMIND_SCORE = 'maxmind_score';
    const COMMENTS      = 'comments';

    protected static $sign = 'rsk';

    protected $entity = 'risk';

    protected $generateIdOnCreate = true;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        self::PAYMENT_ID,
        self::MERCHANT_ID,
        self::FRAUD_TYPE,
        self::SOURCE,
        self::MAXMIND_SCORE,
        self::COMMENTS,
    ];


    protected $visible = [
        self::PAYMENT_ID,
        self::MERCHANT_ID,
        self::FRAUD_TYPE,
        self::SOURCE,
        self::MAXMIND_SCORE,
        self::COMMENTS,
    ];

    protected $public = [
        self::PAYMENT_ID,
        self::MERCHANT_ID,
        self::FRAUD_TYPE,
        self::COMMENTS,
    ];

    protected $publicSetters = [
        self::ID,
        self::PAYMENT_ID,
        self::MERCHANT_ID,
    ];

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function getComments() : string
    {
        return $this->getAttribute(self::COMMENTS);
    }
}
