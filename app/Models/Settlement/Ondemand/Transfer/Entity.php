<?php

namespace RZP\Models\Settlement\Ondemand\Transfer;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $entity = 'settlement.ondemand.transfer';

    protected $generateIdOnCreate = true;

    const ID_LENGTH = 14;
    const ID                     = 'id';
    const AMOUNT                 = 'amount';
    const STATUS                 = 'status';
    const PAYOUT_ID              = 'payout_id';
    const MODE                   = 'mode';
    const PROCESSED_AT           = 'processed_at';
    const REVERSED_AT            = 'reversed_at';
    const LAST_ATTEMPT_AT        = 'last_attempt_at';
    const ATTEMPTS               = 'attempts';
    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';
    const DELETED_AT             = 'deleted_at';

    const PAYOUT_REVERSAL_RETRY_LIMIT = 10;

    protected $fillable = [
        self::ATTEMPTS,
        self::AMOUNT,
        self::STATUS,
        self::REVERSED_AT,
        self::PAYOUT_ID,
        self::MODE,
        self::PROCESSED_AT,
    ];

    public function  getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getPayoutId()
    {
        return $this->getAttribute(self::PAYOUT_ID);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function  getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function  getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function setStatus($status)
    {
        $currentStatus = $this->getStatus();

        Status::validateStatusUpdate($status, $currentStatus);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setAttempts($attempts)
    {
        $this->setAttribute(self::ATTEMPTS, $attempts);
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function setProcessedAt($processedAt)
    {
        $this->setAttribute(self::PROCESSED_AT, $processedAt);
    }

    public function setLastAttemptAt($lastAttemptAt)
    {
        $this->setAttribute(self::LAST_ATTEMPT_AT, $lastAttemptAt);
    }

    public function setReversedAt($reversedAt)
    {
        $this->setAttribute(self::REVERSED_AT, $reversedAt);
    }

    public function setPayoutId($id)
    {
        $this->setAttribute(self::PAYOUT_ID, $id);
    }

    public function canRetry() : bool
    {
        return (($this->getAttempts() < self::PAYOUT_REVERSAL_RETRY_LIMIT) and
                ($this->getCreatedAt() >= Carbon::now()->subDays(2)->getTimestamp()));
    }

}
