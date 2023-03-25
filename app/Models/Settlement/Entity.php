<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Models\Base\Traits\HasBalance;

class Entity extends Base\PublicEntity
{
    use HasBalance;

    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const BANK_ACCOUNT_ID        = 'bank_account_id';
    const BATCH_FUND_TRANSFER_ID = 'batch_fund_transfer_id';
    const AMOUNT                 = 'amount';
    const FEES                   = 'fees';
    const TAX                    = 'tax';
    const STATUS                 = 'status';
    const TRANSACTION_ID         = 'transaction_id';
    const ATTEMPTS               = 'attempts';
    const CHANNEL                = 'channel';
    const UTR                    = 'utr';
    const FAILURE_REASON         = 'failure_reason';
    const REMARKS                = 'remarks';
    const RETURN_UTR             = 'return_utr';
    const PROCESSED_AT           = 'processed_at';
    const SETTLED_ON             = 'settled_on';
    const FTS_TRANSFER_ID        = 'fts_transfer_id';
    const BALANCE_ID             = 'balance_id';
    const IS_NEW_SERVICE         = 'is_new_service';
    const SETTLED_BY             = 'settled_by';
    const OPTIMIZER_PROVIDER     = 'optimizer_provider';
    const JOURNAL_ID             = 'journal_id';

    // Nodal Nodal Settlement Constants
    const GATEWAY                = 'gateway';
    const DESTINATION            = 'destination';

    protected static $sign = 'setl';

    protected $entity = 'settlement';

    protected $fillable = [
        self::FEES,
        self::TAX,
        self::STATUS,
        self::ATTEMPTS,
        self::CHANNEL,
        self::AMOUNT,
        self::PROCESSED_AT,
        self::SETTLED_ON,
        self::IS_NEW_SERVICE,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::BANK_ACCOUNT_ID,
        self::BATCH_FUND_TRANSFER_ID,
        self::AMOUNT,
        self::FEES,
        self::TAX,
        self::STATUS,
        self::TRANSACTION_ID,
        self::ATTEMPTS,
        self::FAILURE_REASON,
        self::REMARKS,
        self::CHANNEL,
        self::UTR,
        self::BALANCE_ID,
        self::PROCESSED_AT,
        self::SETTLED_BY,
        self::OPTIMIZER_PROVIDER,
        self::SETTLED_ON,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::IS_NEW_SERVICE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::STATUS,
        self::FEES,
        self::TAX,
        self::UTR,
        self::SETTLED_BY,
        self::OPTIMIZER_PROVIDER,
        self::CREATED_AT,
    ];

    protected $defaults = [
        self::ATTEMPTS          => 1,
        self::SETTLED_ON        => null,
    ];

    protected $casts = [
        self::ATTEMPTS          => 'int',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PROCESSED_AT,
        self::SETTLED_ON,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::FEES,
        self::TAX,
    ];

    protected $hiddenInReport = [self::SETTLED_ON];

    // --------------------------------- relations -------------------------------

    public function fundTransferAttempts()
    {
        return $this->morphMany('RZP\Models\FundTransfer\Attempt\Entity', 'source');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function bankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity');
    }

    public function bankAccountForFundTransferRecon()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity', self::BANK_ACCOUNT_ID, BankAccount\Entity::ID)
                    ->withTrashed();
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

    public function getIsNewService()
    {
        return $this->getAttribute(self::IS_NEW_SERVICE);
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getFailureReason()
    {
        return $this->getAttribute(self::FAILURE_REASON);
    }

    public function getRemarks()
    {
        return $this->getAttribute(self::REMARKS);
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

    public function getBatchFundTransferId()
    {
        return $this->getAttribute(self::BATCH_FUND_TRANSFER_ID);
    }

    public function getFTSTransferId()
    {
        return $this->getAttribute(self::FTS_TRANSFER_ID);
    }

    public function getSettledOn()
    {
        return $this->getAttribute(self::SETTLED_ON);
    }

    public function hasTransaction()
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID));
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

    public function setUtr(string $utr = null)
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

    public function setTax($tax)
    {
        $this->setAttribute(self::TAX, $tax);
    }

    public function setRemarks($remarks)
    {
        $this->setAttribute(self::REMARKS, $remarks);
    }

    public function setAttempts($count)
    {
        $this->setAttribute(self::ATTEMPTS, $count);
    }

    public function setProcessedAt($date)
    {
        $this->setAttribute(self::PROCESSED_AT, $date);
    }

    public function setSettledOn($date)
    {
        $this->setAttribute(self::SETTLED_ON, $date);
    }

    public function setFTSTransferId($ftsTransferId)
    {
        $this->setAttribute(self::FTS_TRANSFER_ID, $ftsTransferId);
    }

    public function setSettledBy($settledBy)
    {
        $this->setAttribute(self::SETTLED_BY, $settledBy);
    }

    public function setOptimiserProvider($optimiserProvider)
    {
        $this->setAttribute(self::OPTIMIZER_PROVIDER, $optimiserProvider);
    }

    public function setId($id)
    {
        $this->setAttribute(self::ID, $id);
    }

    // --------------------------------- accessors -------------------------------

    protected function getTaxAttribute()
    {
        return (int) $this->attributes[self::TAX];
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

    protected function getSettledOnAttribute()
    {
        $timestamp = $this->attributes[self::SETTLED_ON];

        if ($timestamp !== null)
        {
            return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d/m/Y');
        }

        return null;
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

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement `isStatusFailed`
     * function, to send out summary emails and stuff in bulkRecon.
     *
     * @return bool
     */
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

    public function setPublicAttributeForOptimiser($settlement)
    {
        if (isset($settlement['id']))
        {
            $this->setId($settlement['id']);
        }

        if (isset($settlement['utr']))
        {
            $this->setUtr($settlement['utr']);
        }

        if (isset($settlement['fee']))
        {
            $this->setFees($settlement['fee']);
        }

        if (isset($settlement['tax']))
        {
            $this->setTax($settlement['tax']);
        }

        if (isset($settlement['created_at']))
        {
            $this->setCreatedAt($settlement['created_at']);
        }

        if (isset($settlement['settled_by'])) {
            $this->setSettledBy($settlement['settled_by']);

            if ($settlement['settled_by'] === 'Razorpay') {
                $this->setOptimiserProvider('Razorpay');
            } else if (isset($settlement['provider'])) {
                $this->setOptimiserProvider($settlement['provider']);
            } else {
                $this->setOptimiserProvider('');
            }
        }
    }
}
