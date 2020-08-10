<?php

namespace RZP\Models\Counter;

use App;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\Traits\HasBalance;

/**
 * @property Balance\Entity         $balance
 */
class Entity extends Base\PublicEntity
{
    use HasBalance;

    const ID                                  = 'id';
    const BALANCE_ID                          = 'balance_id';
    const ACCOUNT_TYPE                        = 'account_type';
    const FREE_PAYOUTS_CONSUMED               = 'free_payouts_consumed';
    const FREE_PAYOUTS_CONSUMED_LAST_RESET_AT = 'free_payouts_consumed_last_reset_at';

    protected static $sign = 'count';

    protected $primaryKey = self::ID;

    protected $entity = 'counter';

    protected $fillable = [
        self::ACCOUNT_TYPE,
        self::FREE_PAYOUTS_CONSUMED,
        self::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT,
    ];

    protected $visible = [
        self::ID,
        self::ACCOUNT_TYPE,
        self::BALANCE_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::FREE_PAYOUTS_CONSUMED,
        self::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT,
    ];

    protected static $generators = [
        self::ID,
    ];

    protected $defaults = [
        self::ACCOUNT_TYPE                        => null,
        self::FREE_PAYOUTS_CONSUMED               => 0,
        self::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT => null
    ];

     protected $dates = [
         self::CREATED_AT,
         self::UPDATED_AT,
         self::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT,
     ];

    protected $generateIdOnCreate = true;

    // -------------------- Getters -----------------------------

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    public function getFreePayoutsConsumed()
    {
        return $this->getAttribute(self::FREE_PAYOUTS_CONSUMED);
    }

    public function getFreePayoutsConsumedLastResetAt()
    {
        return $this->getAttribute(self::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT);
    }

    // -------------------- End Getters --------------------------

    // -------------------- Setters ------------------------------

    public function setAccountType($accountType)
    {
        $this->setAttribute(self::ACCOUNT_TYPE, $accountType);
    }

    public function setFreePayoutsConsumed($freePayoutsConsumed)
    {
        $this->setAttribute(self::FREE_PAYOUTS_CONSUMED, $freePayoutsConsumed);
    }

    public function setFreePayoutsConsumedLastResetAt($freePayoutsConsumedLastResetAt)
    {
        $this->setAttribute(self::FREE_PAYOUTS_CONSUMED_LAST_RESET_AT, $freePayoutsConsumedLastResetAt);
    }

    // -------------------- End Setters --------------------------

    // -------------------- Helpers ------------------------------

    public function resetFreePayoutsConsumed()
    {
        $this->setFreePayoutsConsumed(0);

        $freePayoutsConsumedLastResetAt = Carbon::now(Timezone::IST)->firstOfMonth()->getTimestamp();

        $this->setFreePayoutsConsumedLastResetAt($freePayoutsConsumedLastResetAt);
    }

    // -------------------- End Helpers --------------------------
}
