<?php

namespace RZP\Models\Risk;

use RZP\Models\Base;
use RZP\Models\Base\Traits\RevisionableTrait;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    use RevisionableTrait;

    const PAYMENT_ID    = 'payment_id';
    const MERCHANT_ID   = 'merchant_id';
    const FRAUD_TYPE    = 'fraud_type';
    const SOURCE        = 'source';
    const RISK_SCORE    = 'risk_score';
    const COMMENTS      = 'comments';
    const REASON        = 'reason';

    protected static $sign = 'rsk';

    protected $entity = 'risk';

    protected $generateIdOnCreate = true;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        self::FRAUD_TYPE,
        self::SOURCE,
        self::RISK_SCORE,
        self::COMMENTS,
        self::REASON,
    ];

    protected $visible = [
        self::PAYMENT_ID,
        self::MERCHANT_ID,
        self::FRAUD_TYPE,
        self::SOURCE,
        self::RISK_SCORE,
        self::COMMENTS,
        self::REASON,
    ];

    // TODO expose reasons and spec out what we will show
    protected $public = [
        self::PAYMENT_ID,
        self::FRAUD_TYPE,
    ];

    protected $publicSetters = [
        self::ID,
        self::PAYMENT_ID,
    ];

    protected $casts = [
        self::RISK_SCORE => 'float',
    ];

    protected $defaults = [
        self::RISK_SCORE => null,
        self::COMMENTS   => null,
    ];

    // ----------------------Relations -----------------------------

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // ----------------------End Relations --------------------------

    // ----------------------Mutators -------------------------------

    public function setPublicPaymentIdAttribute(array & $attributes)
    {
        $paymentId = $this->getAttribute(self::PAYMENT_ID);

        $attributes[self::PAYMENT_ID] = Payment\Entity::getSignedIdOrNull($paymentId);
    }

    // ----------------------End Mutators --------------------------

    // ----------------------Getters -------------------------------

    public function getComments(): string
    {
        return $this->getAttribute(self::COMMENTS);
    }

    public function getFraudType(): string
    {
        return $this->getAttribute(self::FRAUD_TYPE);
    }

    // ----------------------End Getters ---------------------------

    // ----------------------Setters -------------------------------

    // ----------------------End Setters ---------------------------
}
