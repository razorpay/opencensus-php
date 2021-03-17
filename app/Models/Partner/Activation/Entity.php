<?php

namespace RZP\Models\Partner\Activation;

use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID               = 'merchant_id';
    const ACTIVATION_STATUS         = 'activation_status';
    const ACTIVATED_AT              = 'activated_at';
    const LOCKED                    = 'locked';
    const SUBMITTED                 = 'submitted';
    const SUBMITTED_AT              = 'submitted_at';
    const HOLD_FUNDS                = 'hold_funds';
    const KYC_CLARIFICATION_REASONS = 'kyc_clarification_reasons';


    protected $entity = 'partner_activation';

    protected $primaryKey = self::MERCHANT_ID;

    protected $public = [
        self::MERCHANT_ID,
        self::ACTIVATION_STATUS,
        self::ACTIVATED_AT,
        self::LOCKED,
        self::SUBMITTED,
        self::HOLD_FUNDS,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::KYC_CLARIFICATION_REASONS,
    ];

    protected $fillable = [
        self::ACTIVATION_STATUS,
        self::ACTIVATED_AT,
        self::LOCKED,
        self::SUBMITTED,
        self::SUBMITTED_AT,
        self::HOLD_FUNDS,
        self::KYC_CLARIFICATION_REASONS,
    ];

    protected $casts = [
        self::LOCKED                    => 'bool',
        self::SUBMITTED                 => 'bool',
        self::KYC_CLARIFICATION_REASONS => 'array'
    ];

    protected $defaults = [
        self::SUBMITTED_AT => null,
        self::HOLD_FUNDS   => false,
    ];

    public function getMerchantId(): string
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function setMerchantId(string $merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function getActivationStatus()
    {
        return $this->getAttribute(self::ACTIVATION_STATUS);
    }

    public function setActivationStatus(string $activationStatus)
    {
        $this->setAttribute(self::ACTIVATION_STATUS, $activationStatus);
    }

    public function getActivatedAt()
    {
        return $this->getAttribute(self::ACTIVATED_AT);
    }

    public function setActivatedAt($activatedAt)
    {
        return $this->setAttribute(self::ACTIVATED_AT, $activatedAt);
    }

    public function isLocked(): bool
    {
        return ($this->getAttribute(self::LOCKED) === true);
    }

    public function setLocked(bool $locked)
    {
        $this->setAttribute(self::LOCKED, $locked);
    }

    public function isSubmitted(): bool
    {
        return ($this->getAttribute(self::SUBMITTED) === true);
    }

    public function setHoldFunds($holdFunds)
    {
        $this->setAttribute(self::HOLD_FUNDS, $holdFunds);
    }

    public function isFundsOnHold(): bool
    {
        return ($this->getAttribute(self::HOLD_FUNDS) === true);
    }

    public function releaseFunds()
    {
        $this->setHoldFunds(false);
    }

    public function activationStates()
    {
        return $this->hasMany('\RZP\Models\State\Entity', State\Entity::ENTITY_ID)
                    ->where(State\Entity::ENTITY_TYPE, 'partner_activation');
    }
}
