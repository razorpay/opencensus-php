<?php

namespace RZP\Models\FeeRecovery;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Constants\Table;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::FEE_RECOVERY;
    protected $table  = Table::FEE_RECOVERY;

    const ID                    = 'id';
    const ENTITY_ID             = 'entity_id';
    const ENTITY_TYPE           = 'entity_type';
    const TYPE                  = 'type';
    const RECOVERY_PAYOUT_ID    = 'recovery_payout_id';
    const STATUS                = 'status';
    const ATTEMPT_NUMBER        = 'attempt_number';
    const REFERENCE_NUMBER      = 'reference_number';
    const DESCRIPTION           = 'description';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';

    const PAYOUT                = 'payout';
    const REVERSAL              = 'reversal';
    const NEXT_STATUS           = 'next_status';
    const CURRENT_STATUS        = 'current_status';
    const BALANCE_ID            = 'balance_id';
    const FROM                  = 'from';
    const TO                    = 'to';
    const MERCHANT_ID           = 'merchant_id';
    const FEE                   = 'fee';
    const PAYOUT_IDS            = 'payout_ids';
    const FAILED_PAYOUT_IDS     = 'failed_payout_ids';
    const REVERSAL_IDS          = 'reversal_ids';
    const AMOUNT                = 'amount';

    // Slack channel for alerts
    const RX_CA_RBL_ALERTS = 'rx_ca_rbl_alerts';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::RECOVERY_PAYOUT_ID,
        self::STATUS,
        self::ATTEMPT_NUMBER,
        self::REFERENCE_NUMBER,
        self::DESCRIPTION,
        self::TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::ATTEMPT_NUMBER        => 0,
        self::REFERENCE_NUMBER      => null,
        self::DESCRIPTION           => null,
        self::RECOVERY_PAYOUT_ID    => null,
    ];

    protected $fillable = [
        self::ATTEMPT_NUMBER,
        self::REFERENCE_NUMBER,
        self::DESCRIPTION,
        self::RECOVERY_PAYOUT_ID,
    ];

    const PREVIOUS_RECOVERY_PAYOUT_ID   = 'previous_recovery_payout_id';

    const AUTOMATIC_FEE_RECOVERY_MAX_ATTEMPT_NUMBER = 3;

    // --------------- Getters ---------------

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getAttemptNumber()
    {
        return $this->getAttribute(self::ATTEMPT_NUMBER);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getReferenceNumber()
    {
        return $this->getAttribute(self::REFERENCE_NUMBER);
    }

    // --------------- End Getters ---------------

    // --------------- Setters ---------------

    public function setStatus(string $status)
    {
        $currentStatus = $this->getStatus();

        if ($status === $currentStatus)
        {
            return;
        }

        Status::validateStatusUpdate($status, $currentStatus);

        $this->setAttribute(Entity::STATUS, $status);
    }

    public function setType(string $type)
    {
        Type::validateType($type);

        $this->setAttribute(Entity::TYPE, $type);
    }

    public function setReferenceNumber(string $referenceNumber)
    {
        $this->setAttribute(ENTITY::REFERENCE_NUMBER, $referenceNumber);
    }

    public function setDescription(string $description)
    {
        $this->setAttribute(ENTITY::DESCRIPTION, $description);
    }

    public function setRecoveryPayoutId(string $recoveryPayoutId)
    {
        $this->setAttribute(ENTITY::RECOVERY_PAYOUT_ID, $recoveryPayoutId);
    }

    public function setAttemptNumber(int $attemptNumber)
    {
        $this->setAttribute(ENTITY::ATTEMPT_NUMBER, $attemptNumber);
    }

    // --------------- End Setters ---------------

    // --------------- Relations ---------------

    /**
     * This can be either reversal or payout
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function entity()
    {
        return $this->morphTo();
    }

    public function payout()
    {
        return $this->morphTo(Entity::PAYOUT, Entity::ENTITY_TYPE, Entity::ENTITY_ID);
    }

    public function reversal()
    {
        return $this->morphTo(Entity::REVERSAL, Entity::ENTITY_TYPE, Entity::ENTITY_ID);
    }

    public function recoveryPayout()
    {
        return $this->belongsTo(Payout\Entity::class);
    }

    // --------------- End Relations ---------------

}
