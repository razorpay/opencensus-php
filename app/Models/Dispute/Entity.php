<?php

namespace RZP\Models\Dispute;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Transaction;

class Entity extends Base\PublicEntity
{
    use Base\Traits\RevisionableTrait;

    const MERCHANT_ID             = 'merchant_id';
    const PAYMENT_ID              = 'payment_id';
    const TRANSACTION_ID          = 'transaction_id';
    const AMOUNT                  = 'amount';
    const AMOUNT_DEDUCTED         = 'amount_deducted';
    const AMOUNT_REVERSED         = 'amount_reversed';
    const CURRENCY                = 'currency';
    const DEDUCT_AT_ONSET         = 'deduct_at_onset';
    const GATEWAY_DISPUTE_ID      = 'gateway_dispute_id';
    const REASON_ID               = 'reason_id';
    const REASON_CODE             = 'reason_code';
    const REASON_DESCRIPTION      = 'reason_description';
    const GATEWAY_DISPUTE_STATUS  = 'gateway_dispute_status';
    const RAISED_ON               = 'raised_on';
    const EXPIRES_ON              = 'expires_on';
    const STATUS                  = 'status';
    const PHASE                   = 'phase';
    const COMMENTS                = 'comments';
    const CREATED_AT              = 'created_at';
    const UPDATED_AT              = 'updated_at';
    const RESOLVED_AT             = 'resolved_at';

    protected static $sign = 'disp';

    protected $entity = 'dispute';

    protected $generateIdOnCreate = true;

    protected $revisionCreationsEnabled = true;

    protected $revisionEnabled = true;

    protected $fillable = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::GATEWAY_DISPUTE_ID,
        self::GATEWAY_DISPUTE_STATUS,
        self::DEDUCT_AT_ONSET,
        self::REASON_CODE,
        self::REASON_DESCRIPTION,
        self::RAISED_ON,
        self::EXPIRES_ON,
        self::STATUS,
        self::PHASE,
        self::AMOUNT_DEDUCTED,
        self::AMOUNT_REVERSED,
        self::COMMENTS,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::REASON_ID,
        self::TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::AMOUNT_DEDUCTED,
        self::AMOUNT_REVERSED,
        self::DEDUCT_AT_ONSET,
        self::GATEWAY_DISPUTE_ID,
        self::GATEWAY_DISPUTE_STATUS,
        self::REASON_CODE,
        self::REASON_DESCRIPTION,
        self::RAISED_ON,
        self::EXPIRES_ON,
        self::STATUS,
        self::PHASE,
        self::COMMENTS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::REASON_CODE,
        self::REASON_DESCRIPTION,
        self::RAISED_ON,
        self::EXPIRES_ON,
        self::STATUS,
        self::PHASE,
        self::COMMENTS,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::AMOUNT          => 'int',
        self::AMOUNT_DEDUCTED => 'int',
        self::AMOUNT_REVERSED => 'int',
        self::DEDUCT_AT_ONSET => 'bool',
    ];

    protected $guarded = [self::ID];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::RESOLVED_AT,
        self::RAISED_ON,
        self::EXPIRES_ON
    ];

    protected $defaults = [
        self::STATUS          => Status::OPEN,
        self::DEDUCT_AT_ONSET => false,
        self::AMOUNT_DEDUCTED => 0,
        self::AMOUNT_REVERSED => 0
    ];

    protected $amounts = [
        self::AMOUNT,
        self::AMOUNT_REVERSED,
        self::AMOUNT_DEDUCTED,
    ];

    // ----------------------- Setters -----------------------------------------

    public function setAmountDeducted(int $amount)
    {
        $this->setAttribute(self::AMOUNT_DEDUCTED, $amount);
    }

    public function setAmountReversed(int $amount)
    {
        $this->setAttribute(self::AMOUNT_REVERSED, $amount);
    }

    public function setCurrency(string $currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setReasonDescription(string $description)
    {
        $this->setAttribute(self::REASON_DESCRIPTION, $description);
    }

    public function setReasonCode(string $code)
    {
        $this->setAttribute(self::REASON_CODE, $code);
    }

    public function setResolvedAt(int $time)
    {
        $this->setAttribute(self::RESOLVED_AT, $time);
    }

    public function setExpiresOn(int $time)
    {
        $this->setAttribute(self::EXPIRES_ON, $time);
    }

    // ----------------------- Setters Ends-------------------------------------

    // ----------------------- Getters -----------------------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getAmountDeducted()
    {
        return $this->getAttribute(self::AMOUNT_DEDUCTED);
    }

    public function getAmountReversed()
    {
        return $this->getAttribute(self::AMOUNT_REVERSED);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getExpiresOn()
    {
        return $this->getAttribute(self::EXPIRES_ON);
    }

    public function getResolvedAt()
    {
        return $this->getAttribute(self::RESOLVED_AT);
    }

    public function getRaisedOn()
    {
        return $this->getAttribute(self::RAISED_ON);
    }

    public function getDeductAtOnset()
    {
        return $this->getAttribute(self::DEDUCT_AT_ONSET);
    }

    // ----------------------- Getters Ends-------------------------------------

    // Add toArrayAdmin, toArrayReport

    // --------------- Relation to other entities ------------------------------

    public function payment()
    {
        return $this->belongsTo(Payment\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction\Entity::class);
    }

    public function reason()
    {
        return $this->belongsTo(Reason\Entity::class);
    }

    // --------------- Relation to other entity section ends --------------------

    public function isClosed(): bool
    {
        return (in_array($this->getStatus(), Status::getClosedStatuses(), true) === true);
    }

    public function isLost(): bool
    {
        return ($this->getStatus() === Status::LOST);
    }

    public function isWon(): bool
    {
        return ($this->getStatus() === Status::WON);
    }
}
