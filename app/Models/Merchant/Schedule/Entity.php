<?php

namespace RZP\Models\Merchant\Schedule;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const METHOD            = 'method';
    const SCHEDULE_ID       = 'schedule_id';

    protected $primaryKey = self::MERCHANT_ID;

    protected $entity = 'merchant_schedule';

    protected $fillable = [
        self::METHOD,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::METHOD,
        self::SCHEDULE_ID,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::METHOD,
        self::SCHEDULE_ID
    ];

    protected $defaults = [
        self::METHOD    => null,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function schedule()
    {
        return $this->belongsTo('RZP\Models\Schedule\Entity');
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }
}
