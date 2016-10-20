<?php

namespace RZP\Models\Merchant\Schedule;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID          = 'id';
    const MERCHANT_ID = 'merchant_id';
    const SCHEDULE_ID = 'schedule_id';
    const LAST_RUN    = 'last_run';

    protected $fillable = array(
        self::ID,
        self::MERCHANT_ID,
        self::SCHEDULE_ID,
        self::LAST_RUN,
    );

    protected $table = \RZP\Constants\Table::MERCHANT_SCHEDULE;

    protected $entity = 'merchant_schedule';

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity', self::MERCHANT_ID);
    }

    public function schedule()
    {
        return $this->belongsTo('RZP\Models\Schedule\Entity', self::SCHEDULE_ID);
    }

    public function getLastRun()
    {
        return $this->getAttribute(self::LAST_RUN);
    }
}