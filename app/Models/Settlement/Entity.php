<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Models\Transaction;
use RZP\Exception;

class Entity extends Base\PublicEntity
{
    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const BANK_ACCOUNT_ID        = 'bank_account_id';
    const BATCH_FUND_TRANSFER_ID = 'batch_fund_transfer_id';
    const AMOUNT                 = 'amount';
    const FEES                   = 'fees';
    const SERVICE_TAX            = 'service_tax';
    const STATUS                 = 'status';
    const TRANSACTION_ID         = 'transaction_id';
    const ATTEMPTS               = 'attempts';
    const CHANNEL                = 'channel';
    const UTR                    = 'utr';
    const FAILURE_REASON         = 'failure_reason';
    const REMARKS                = 'remarks';
    const RETURN_UTR             = 'return_utr';
    const PROCESSED_AT           = 'processed_at';

    protected static $sign = 'setl';

    protected $entity = 'settlement';

    protected $fillable = [
        self::FEES,
        self::SERVICE_TAX,
        self::STATUS,
        self::MERCHANT_ID,
        self::BANK_ACCOUNT_ID,
        self::TRANSACTION_ID,
        self::ATTEMPTS,
        self::CHANNEL,
        self::AMOUNT,
        self::PROCESSED_AT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::BANK_ACCOUNT_ID,
        self::BATCH_FUND_TRANSFER_ID,
        self::AMOUNT,
        self::FEES,
        self::SERVICE_TAX,
        self::STATUS,
        self::TRANSACTION_ID,
        self::ATTEMPTS,
        self::FAILURE_REASON,
        self::REMARKS,
        self::CHANNEL,
        self::UTR,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PROCESSED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::STATUS,
        self::FEES,
        self::SERVICE_TAX,
        self::UTR,
        self::PROCESSED_AT,
        self::CREATED_AT
    ];

    protected $defaults = [
        self::ATTEMPTS => 1,
    ];

    protected $casts = [
        self::ATTEMPTS => 'int',
    ];

    protected $dates = [
        self::PROCESSED_AT,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::FEES,
        self::SERVICE_TAX,
    ];

    // --------------------------------- relations -------------------------------

    public function fundTransferAttempts()
    {
        return $this->morphMany('RZP\Models\FundTransfer\Attempt\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function bankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity');
    }

    // Fetches the transaction of type Settlement
    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function batchFundTransfer()
    {
        return $this->belongsTo('RZP\Models\FundTransfer\Batch\Entity');
    }

    // Fetches all types of transactions for the given settlement
    public function setlTransactions()
    {
        return $this->hasMany('RZP\Models\Transaction\Entity');
    }

    public function adjustment()
    {
        return $this->hasOne('RZP\Models\Adjustment\Entity');
    }

    // --------------------------------- getters -------------------------------

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getFees()
    {
        return $this->getAttribute(self::FEES);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getServiceTax()
    {
        return $this->getAttribute(self::SERVICE_TAX);
    }

    public function getFailureReason()
    {
        return $this->getAttribute(self::FAILURE_REASON);
    }

    public function getRemarks()
    {
        return $this->getAttribute(self::REMARKS);
    }

    public function getVersion()
    {
        return $this->getAttribute(self::VERSION);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function getProcessedAt()
    {
        return $this->getAttribute(self::PROCESSED_AT);
    }

    // --------------------------------- setters -------------------------------

    public function setAmount($amount)
    {
        if (($amount <= 0) or
            (is_int($amount) === false))
        {
            throw new Exception\LogicException(
                'Something very wrong is happening! ' .
                'Settlement amount should not be 0 or -ve',
                null,
                ['amount' => $amount]);
        }

        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setStatus($status = Status::CREATED)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setChannel($channel)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setReturnUtr($utr)
    {
        $this->setAttribute(self::RETURN_UTR, $utr);
    }

    public function setFailureReason($reason)
    {
        $this->setAttribute(self::FAILURE_REASON, $reason);
    }

    public function setFees($fee)
    {
        $this->setAttribute(self::FEES, $fee);
    }

    public function setServiceTax($serviceTax)
    {
        $this->setAttribute(self::SERVICE_TAX, $serviceTax);
    }

    public function setRemarks($remarks)
    {
        $this->setAttribute(self::REMARKS, $remarks);
    }

    public function setVersion($version)
    {
        $this->setAttribute(self::VERSION, $version);
    }

    public function setAttempts($count)
    {
        $this->setAttribute(self::ATTEMPTS, $count);
    }

    public function setProcessedAt($date)
    {
        $this->setAttribute(self::PROCESSED_AT, $date);
    }

    // --------------------------------- modifiers -------------------------------

    protected function getServiceTaxAttribute()
    {
        return (int) $this->attributes[self::SERVICE_TAX];
    }

    protected function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    protected function getFeesAttribute()
    {
        $fee = $this->attributes[self::FEES];

        if ($fee !== null)
        {
            $fee = (int) $fee;
        }

        return $fee;
    }

    protected function getProcessedAtAttribute()
    {
        $processedAt = $this->attributes[self::PROCESSED_AT];

        return Carbon::createFromTimestamp($processedAt, 'Asia/Kolkata')->toDateString();
    }

    // ------------------------------- mutators --------------------------------

    protected function setRemarksAttribute($remarks)
    {
        $this->attributes[self::REMARKS] = substr($remarks, 0, 255);
    }

    // ------------------------------- end mutators ----------------------------

    // --------------------------------- entity methods -------------------------------

    public function isStatusCreated()
    {
        return ($this->getStatus() === Status::CREATED);
    }

    public function isStatusFailed()
    {
        return ($this->getStatus() === Status::FAILED);
    }

    public function isStatusProcessed()
    {
        return $this->getStatus() === Status::PROCESSED;
    }

    public function isPendingReconciliation()
    {
        return $this->isStatusCreated();
    }

    public function save(array $options = array())
    {
        return parent::save($options);
    }

    public function incrementAttempts()
    {
        $this->increment(self::ATTEMPTS);
    }
}
