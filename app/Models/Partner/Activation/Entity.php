<?php

namespace RZP\Models\Partner\Activation;

use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Merchant;

/**
 * @property Merchant\Detail\Entity  $merchantDetail
 */
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
    const REJECTION_REASONS         = 'rejection_reasons';
    const CLARIFICATION_REASONS     = 'clarification_reasons';
    const ADDITIONAL_DETAILS        = 'additional_details';
    const REVIEWER_ID               = 'reviewer_id';

    const ALLOWED_NEXT_ACTIVATION_STATUSES = 'allowed_next_activation_statuses';


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
        self::SUBMITTED_AT,
        self::KYC_CLARIFICATION_REASONS,
        self::ALLOWED_NEXT_ACTIVATION_STATUSES,
        self::REVIEWER_ID
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

    protected $publicSetters = [
        self::ALLOWED_NEXT_ACTIVATION_STATUSES,
        self::REVIEWER_ID,
    ];

    protected $adminOnlyPublic = [
        self::REVIEWER_ID,
    ];

    protected $casts = [
        self::LOCKED                    => 'bool',
        self::SUBMITTED                 => 'bool',
        self::HOLD_FUNDS                => 'bool',
        self::KYC_CLARIFICATION_REASONS => 'array'
    ];

    protected $defaults = [
        self::SUBMITTED_AT => null,
        self::HOLD_FUNDS   => false,
    ];

    public function getId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getMerchantId(): string
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function merchantDetail()
    {
        return $this->belongsTo(Merchant\Detail\Entity::class, self::MERCHANT_ID, self::MERCHANT_ID);
    }

    public function reviewer()
    {
        return $this->belongsTo(\RZP\Models\Admin\Admin\Entity::class);
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

    public function getFundsOnHold(): bool
    {
        return $this->getAttribute(self::HOLD_FUNDS);
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

    public function activationState()
    {
        return $this->activationStates()
                    ->orderBy(State\Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getActivationStatusChangeLog()
    {
        return $this->activationStates()
                    ->orderBy(State\Entity::CREATED_AT)
                    ->get();
    }

    public function setKycClarificationReasons(array $reasons)
    {
        return $this->setAttribute(self::KYC_CLARIFICATION_REASONS, $reasons);
    }

    public function getKycClarificationReasons()
    {
        return $this->getAttribute(self::KYC_CLARIFICATION_REASONS);
    }

    public function deactivate()
    {
        $this->setHoldFunds(true);
    }

    protected function setPublicAllowedNextActivationStatusesAttribute(array & $array)
    {
        $activationStatus = $this->getActivationStatus();

        $allowedNextActivationStatuses = [];

        if (empty($activationStatus) === false)
        {
            $allowedNextActivationStatuses = Constants::NEXT_ACTIVATION_STATUSES_MAPPING[$activationStatus];
        }

        $array[self::ALLOWED_NEXT_ACTIVATION_STATUSES] = $allowedNextActivationStatuses;
    }

    public function setPublicReviewerIdAttribute(array &$attributes)
    {
        $adminId = $this->getAttribute(self::REVIEWER_ID);

        $attributes[self::REVIEWER_ID] = \RZP\Models\Admin\Admin\Entity::getSignedIdOrNull($adminId);
    }
}

