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
        self::PAYMENT_ID,
        self::MERCHANT_ID,
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

    protected $public = [
        self::PAYMENT_ID,
        self::MERCHANT_ID,
        self::FRAUD_TYPE,
        self::COMMENTS,
        self::REASON,
    ];

    protected $publicSetters = [
        self::ID,
        self::PAYMENT_ID,
        self::MERCHANT_ID,
    ];

    protected $casts = [
        self::RISK_SCORE => 'float',
    ];

    protected $defaults = [
        self::RISK_SCORE => 0,
    ];


    // ---------------------------------Relations -------------------

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // -------------------------------End Relations ------------------

    // -------------------------------------- Mutators ---------------

    public function setPublicPaymentIdAttribute(array & $attributes)
    {
        $paymentId = $this->getAttribute(static::PAYMENT_ID);

        if ($paymentId !== null)
        {
            $attributes[static::PAYMENT_ID] = Payment\Entity::getSignedId($paymentId);
        }
    }

    public function setPublicMerchantIdAttribute(array & $attributes)
    {
        $merchantId = $this->getAttribute(static::MERCHANT_ID);

        if ($merchantId !== null)
        {
            $attributes[static::MERCHANT_ID] = Merchant\Entity::getSignedId($merchantId);
        }
    }

    // -------------------------------------- End Mutators -----------


    // -------------------------------------- Getters ----------------

    public function getComments(): string
    {
        return $this->getAttribute(self::COMMENTS);
    }

    // -------------------------------------- End Getters ------------


    // -------------------------------------- Setters ----------------

    // -------------------------------------- End Setters ------------

    public function associateRelatedEntites(array $input)
    {
        if (empty($input[self::PAYMENT_ID]) === false)
        {
            $this->payment()->associate($input[self::PAYMENT_ID]);
        }

        if (empty($input[self::MERCHANT_ID]) === false)
        {
            $this->merchant()->associate($input[self::MERCHANT_ID]);
        }
    }
}
