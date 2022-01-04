<?php

namespace RZP\Models\Settlement\OndemandPayout;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $generateIdOnCreate = true;

    protected static $sign = 'setlodp';

    protected $entity = 'settlement.ondemand_payout';

    public $scheduled = false;

    const PAYMENT_METHOD = 'fund_transfer';
    const TRANSACTION    = 'transaction';
    const ID_LENGTH = 14;

    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const USER_ID                = 'user_id';
    const SETTLEMENT_ONDEMAND_ID = 'settlement_ondemand_id';
    const PAYOUT_ID              = 'payout_id';
    const ENTITY_TYPE            = 'entity_type';
    const MODE                   = 'mode';
    const INITIATED_AT           = 'initiated_at';
    const PROCESSED_AT           = 'processed_at';
    const REVERSED_AT            = 'reversed_at';
    const AMOUNT                 = 'amount';
    const FEES                   = 'fees';
    const TAX                    = 'tax';
    const UTR                    = 'utr';
    const STATUS                 = 'status';
    const FAILURE_REASON         = 'failure_reason';
    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';
    const DELETED_AT             = 'deleted_at';

    protected $public = [
        self::ID,
        self::ENTITY,
        self::INITIATED_AT,
        self::PROCESSED_AT,
        self::REVERSED_AT,
        self::AMOUNT,
        self::FEES,
        self::TAX,
        self::UTR,
        self::STATUS,
        self::CREATED_AT,
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::USER_ID,
        self::SETTLEMENT_ONDEMAND_ID,
        self::PAYOUT_ID,
        self::MODE,
        self::INITIATED_AT,
        self::PROCESSED_AT,
        self::REVERSED_AT,
        self::AMOUNT,
        self::FEES,
        self::TAX,
        self::UTR,
        self::STATUS,
        self::FAILURE_REASON,
    ];

    public function settlementOndemand()
    {
        return $this->belongsTo(\RZP\Models\Settlement\Ondemand\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(\RZP\Models\User\Entity::class);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

    public function getMerchantId(): string
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getPayoutAmount()
    {
        return $this->getAttribute(self::AMOUNT) - $this->getAttribute(self::FEES);
    }

    public function setTax($tax)
    {
        $this->setAttribute(self::TAX, $tax);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setProcessedAt($time)
    {
        $this->setAttribute(self::PROCESSED_AT, $time);
    }

    public function setFees($fees)
    {
        $this->setAttribute(self::FEES, $fees);
    }

    public function getOndemandId(): string
    {
        return $this->getAttribute(self::SETTLEMENT_ONDEMAND_ID);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getProcessedAt()
    {
        return $this->getAttribute(self::PROCESSED_AT);
    }

    public function getMethod()
    {
        return self::PAYMENT_METHOD;
    }

    public function getBaseAmount()
    {
        return $this->getAmount();
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getFees()
    {
        return $this->getAttribute(self::FEES);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getPayoutId()
    {
        return $this->getAttribute(self::PAYOUT_ID);
    }

    public function getAmountToBeSettled()
    {
        return ($this->getAttribute(self::AMOUNT) - $this->getAttribute(self::FEES));
    }

    public function setFailureReason($reverseReason)
    {
        $this->setAttribute(self::FAILURE_REASON, $reverseReason);
    }

    public function setStatus($status)
    {
        $currentStatus = $this->getStatus();

        Status::validateStatusUpdate($status, $currentStatus);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setPayoutId($payoutId)
    {
        $this->setAttribute(self::PAYOUT_ID, $payoutId);
    }

    public function setInitiatedAt($time)
    {
        $this->setAttribute(self::INITIATED_AT, $time);
    }

    public function setReversedAt($time)
    {
        $this->setAttribute(self::REVERSED_AT, $time);
    }

    public function setEntityType($entityType)
    {
        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function toArrayPublic()
    {
        $arr = parent::toArrayPublic();

        return [
            self::ID                    => $arr[self::ID],
            self::ENTITY                => $arr[self::ENTITY],
            self::INITIATED_AT          => $arr[self::INITIATED_AT],
            self::PROCESSED_AT          => $arr[self::PROCESSED_AT],
            self::REVERSED_AT           => $arr[self::REVERSED_AT],
            self::AMOUNT                => $arr[self::AMOUNT],
            'amount_settled'            => $this->getPayoutAmount(),
            self::FEES                  => $arr[self::FEES],
            self::TAX                   => $arr[self::TAX],
            self::UTR                   => $arr[self::UTR],
            self::STATUS                => $arr[self::STATUS],
            self::CREATED_AT            => $arr[self::CREATED_AT],
        ];
    }
}
