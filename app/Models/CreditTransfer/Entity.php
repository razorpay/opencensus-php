<?php


namespace RZP\Models\CreditTransfer;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Base\Traits\HasBalance;

class Entity extends Base\PublicEntity
{
    use HasBalance;

    protected static $sign = 'ct_';

    protected $entity = 'credit_transfer';

    protected $generateIdOnCreate = true;

    const ID                 = 'id';
    const MERCHANT_ID        = 'merchant_id';
    const BALANCE_ID         = 'balance_id';
    const AMOUNT             = 'amount';
    const CURRENCY           = 'currency';
    const TRANSACTION_ID     = 'transaction_id';
    const UTR                = 'utr';
    const ENTITY_ID          = 'entity_id';
    const ENTITY_TYPE        = 'entity_type';
    const MODE               = 'mode';
    const CHANNEL            = 'channel';
    const DESCRIPTION        = 'description';
    const STATUS             = 'status';

    // payer details
    const PAYER_NAME         = 'payer_name';
    const PAYER_ACCOUNT      = 'payer_account';
    const PAYER_IFSC         = 'payer_ifsc';

    // payee details
    const PAYEE_ACCOUNT_TYPE = 'payee_account_type';
    const PAYEE_ACCOUNT_ID   = 'payee_account_id';

    // timestamps
    const FAILED_AT          = 'failed_at';
    const PROCESSED_AT       = 'processed_at';

    protected $fillable = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CHANNEL,
        self::MODE,
        self::DESCRIPTION,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::PAYER_ACCOUNT,
        self::PAYER_NAME,
        self::PAYER_IFSC,
        self::PAYEE_ACCOUNT_ID,
        self::PAYEE_ACCOUNT_TYPE,
        self::FAILED_AT,
        self::PROCESSED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::MODE,
        self::DESCRIPTION,
        self::TRANSACTION_ID,
        self::UTR,
        self::CREATED_AT,
        self::PROCESSED_AT,
        self::FAILED_AT,
        self::UPDATED_AT
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::BALANCE_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::TRANSACTION_ID,
        self::UTR,
        self::MODE,
        self::CHANNEL,
        self::DESCRIPTION,
        self::STATUS,
        self::PAYER_ACCOUNT,
        self::PAYER_NAME,
        self::PAYER_IFSC,
        self::PAYEE_ACCOUNT_ID,
        self::PAYEE_ACCOUNT_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PROCESSED_AT,
        self::FAILED_AT
    ];

    protected static $generators = [
        self::ID
    ];

    protected $defaults = [
        self::UTR            => null,
        self::TRANSACTION_ID => null,
        self::FAILED_AT      => null,
        self::PROCESSED_AT   => null
    ];

    protected $amounts = [
        self::AMOUNT
    ];

    protected $publicSetters = [
        self::ID,
    ];

    // ----------------------- Associations ------------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    // -------------------------- Getters --------------------------------------

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getProcessedAt()
    {
        return $this->getAttribute(self::PROCESSED_AT);
    }

    public function getFailedAt()
    {
        return $this->getAttribute(self::FAILED_AT);
    }

    // ----------------------- Setters -----------------------------------------

    public function setStatus($status)
    {
        $currentStatus = $this->getStatus();

        if ($currentStatus === $status)
        {
            return;
        }

        $this->setAttribute(self::STATUS, $status);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setProcessedAt($date)
    {
        $this->setAttribute(self::PROCESSED_AT, $date);
    }

    public function setFailedAt($date)
    {
        $this->setAttribute(self::FAILED_AT, $date);
    }

    // ============================= MUTATORS =============================

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

    // ============================= END MUTATORS =============================

    // ----------------------------------------------------------------

    public function isStatusProcessed()
    {
        return ($this->getAttribute(self::STATUS) === Status::PROCESSED);
    }

    public function isStatusFailed()
    {
        return ($this->getAttribute(self::STATUS) === Status::FAILED);
    }

    public function hasTransaction()
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID));
    }
}
