<?php

namespace RZP\Models\PayoutsDetails;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Payout;
use RZP\Constants\Table;

/**
 * @property Payout\Entity $payout
 */
class Entity extends PublicEntity
{
    const PAYOUT_ID            = 'payout_id';
    const QUEUE_IF_LOW_BALANCE_FLAG = 'queue_if_low_balance_flag';

    // Relations
    const PAYOUT = 'payout';

    protected $entity = Table::PAYOUTS_DETAILS;

    protected $primaryKey = self::PAYOUT_ID;

    protected $fillable   = [
        self::PAYOUT_ID,
        self::QUEUE_IF_LOW_BALANCE_FLAG,
    ];

    protected $visible = [
        self::PAYOUT_ID,
        self::QUEUE_IF_LOW_BALANCE_FLAG,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];


    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::QUEUE_IF_LOW_BALANCE_FLAG => 0,
    ];

    protected $casts = [
        self::QUEUE_IF_LOW_BALANCE_FLAG => 'bool',
    ];

    // ============================= RELATIONS =============================

    public function payout()
    {
        return $this->belongsTo(Payout\Entity::class);
    }

    // ============================= END RELATIONS =============================


    // ============================= GETTERS =============================

    public function getQueueIfLowBalanceFlag()
    {
        return $this->getAttribute(self::QUEUE_IF_LOW_BALANCE_FLAG);
    }

    public function getPayoutId()
    {
        return $this->getAttribute(self::PAYOUT_ID);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setQueueIfLowBalanceFlag(bool $queueIfLowBalanceFlag)
    {
        $this->setAttribute(self::QUEUE_IF_LOW_BALANCE_FLAG, $queueIfLowBalanceFlag);
    }

    // ============================= END SETTERS =============================
}
