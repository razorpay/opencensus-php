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

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID
    ];

    protected $visible = [
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::RECOVERY_PAYOUT_ID,
        self::STATUS,
        self::ATTEMPT_NUMBER,
        self::REFERENCE_NUMBER,
        self::DESCRIPTION,
        self::TYPE,
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
    ];

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
