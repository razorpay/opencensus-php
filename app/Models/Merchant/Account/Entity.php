<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;
use RZP\Models\Schedule\Task as ScheduleTask;

class Entity extends Merchant\Entity
{
    const SETTLEMENT_SCHEDULES = 'settlement_schedules';

    protected static $sign = 'acc';

    protected static $delimiter = '_';

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::EMAIL,
        self::ACTIVATED,
        self::ACTIVATED_AT,
        self::SETTLEMENT_SCHEDULES,
        self::LIVE,
        self::HOLD_FUNDS,
        self::CREATED_AT,
     ];

    protected $embeddedRelations = [
        self::SETTLEMENT_SCHEDULES
    ];

    protected static $morphMap = [];

    public function settlementSchedules()
    {
        return $this->morphOne(ScheduleTask\Entity::class, 'entity');
    }

    public function getMorphClass()
    {
        return 'merchant';
    }

    public function scopeMerchantId($query, $merchantId)
    {
        $merchantIdColumn = $this->dbColumn(Entity::PARENT_ID);

        $query->where($merchantIdColumn, '=', $merchantId);
    }
}
