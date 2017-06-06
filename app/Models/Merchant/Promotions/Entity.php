<?php

namespace RZP\Models\Merchant\Promotion;

use RZP\Exception;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID             = 'id';
    const MERCHANT_ID    = 'merchant_id';
    const PROMOTION_ID   = 'promotion_id';
    const START_TIME     = 'start_time';
    const REMAINING_RUNS = 'remaining_runs';
    const EXPIRED        = 'expired';


    protected $defaults = [
        self::EXPIRED => false
    ];

    protected $casts = [
        self::EXPIRED => 'boolean'
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function promotion()
    {
        return $this->belongsTo('RZP\Models\Promotion\Entity');
    }
}
