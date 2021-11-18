<?php

namespace RZP\Models\Payout\PayoutsIntermediateTransactions;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Base\Traits\HardDeletes;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Payout\Entity as PayoutEntity;

class Entity extends Base\PublicEntity
{
    // Traits
    use HasBalance;
    use HardDeletes;

    // properties
    protected $entity = EntityConstants::PAYOUTS_INTERMEDIATE_TRANSACTIONS;

    protected $table  = Table::PAYOUTS_INTERMEDIATE_TRANSACTIONS;

    protected $generateIdOnCreate = true;

    protected static $sign = 'ptl';

    // Schema Constants
    const ID                     = 'id';
    const PAYOUT_ID              = 'payout_id';
    const TRANSACTION_ID         = 'transaction_id';
    const CLOSING_BALANCE        = 'closing_balance';
    const TRANSACTION_CREATED_AT = 'transaction_created_at';
    const STATUS                 = 'status';
    const AMOUNT                 = 'amount';
    const PENDING_AT             = 'pending_at';
    const COMPLETED_AT           = 'completed_at';
    const REVERSED_AT            = 'reversed_at';
    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';
    // Schema Constants End

    // ================================== Other Constants =========================

    // ================================== END Other Constants =========================

    // defaults
    protected $defaults = [
        self::STATUS => null
    ];

    // generators
    protected static $generators = [
        self::ID,
    ];

    protected $amounts = [
        self::AMOUNT,
    ];

    protected $casts = [
        self::AMOUNT      => 'int',
    ];

    // fillable attributes
    protected $fillable = [
        self::PAYOUT_ID,
        self::AMOUNT,
        self::TRANSACTION_ID,
        self::TRANSACTION_CREATED_AT,
        self::CLOSING_BALANCE
    ];

    // visible attributes
    protected $visible = [
        self::ID,
        self::PAYOUT_ID,
        self::TRANSACTION_ID,
        self::TRANSACTION_CREATED_AT,
        self::CLOSING_BALANCE,
        self::AMOUNT,
        self::STATUS,
        self::PENDING_AT,
        self::COMPLETED_AT,
        self::REVERSED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // public attributes
    protected $public = [
        self::ID,
        self::PAYOUT_ID,
        self::TRANSACTION_ID,
        self::TRANSACTION_CREATED_AT,
        self::CLOSING_BALANCE,
        self::AMOUNT,
        self::STATUS,
        self::PENDING_AT,
        self::COMPLETED_AT,
        self::REVERSED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // public setters
    protected $publicSetters = [
        self::ID,
    ];

    // ============================= GETTERS ===============================

    public function getPayoutId()
    {
        return $this->getAttribute(self::PAYOUT_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getTransactionCreatedAt()
    {
        return $this->getAttribute(self::TRANSACTION_CREATED_AT);
    }

    public function getClosingBalance()
    {
        return $this->getAttribute(self::CLOSING_BALANCE);
    }

    // ============================= END GETTERS ===========================

    // ============================= SETTERS ===========================

    public function setStatus($status)
    {
        $currentStatus = $this->getStatus();

        if ($currentStatus === $status)
        {
            return;
        }

        Status::validateStatusUpdate($status, $currentStatus);

        $this->setAttribute(self::STATUS, $status);
    }

    // ============================= END SETTERS ===========================

    // ============================= MUTATORS ===========================

    protected function setStatusAttribute($status)
    {
        $this->attributes[self::STATUS] = $status;

        if (in_array($status, Status::$timestampedStatuses, true) === true)
        {
            $timestampKey = $status . '_at';

            $currentTime = Carbon::now()->getTimestamp();

            $this->setAttribute($timestampKey, $currentTime);
        }
    }

    // ============================= RELATIONS ===========================
    public function payout()
    {
        return $this->belongsTo(PayoutEntity::class);
    }
}

