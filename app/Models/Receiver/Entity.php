<?php

namespace RZP\Models\Receiver;

use RZP\Models\Base;
use RZP\Constants\Entity as Constants;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                   = 'id';
    const ONE_TIME_USE         = 'one_time_use';
    const EXPECTED_AMOUNT      = 'expected_amount';
    const ACCEPT_PARTIAL       = 'accept_partial';
    const AMOUNT_PAID          = 'amount_paid';
    const ENTITY_TYPE          = 'entity_type';
    const ENTITY_ID            = 'entity_id';
    const VALID                = 'valid';

    const DELETED_AT = 'deleted_at';

    protected $fillable = array(
        self::ID,
        self::ONE_TIME_USE,
        self::EXPECTED_AMOUNT,
        self::ACCEPT_PARTIAL,
        self::AMOUNT_PAID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::VALID,
    );

    protected $public = array(
        self::ID,
        self::ONE_TIME_USE,
        self::EXPECTED_AMOUNT,
        self::ACCEPT_PARTIAL,
        self::AMOUNT_PAID,
    );

    protected $casts = [
        self::ONE_TIME_USE         => 'bool',
        self::ACCEPT_PARTIAL       => 'bool',
        self::VALID                => 'bool',
    ];

    protected $generateIdOnCreate = true;

    protected $entity = Constants::RECEIVER;

    // ----------------------- Associations ----------------------------------------

    public function entity()
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    public function account()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity', 'id');
    }

    public function entityAssociate(Base\Entity $entity)
    {
        $this->setEntityType($entity->getEntityName());

        $this->entity()->associate($entity);
    }

    // ----------------------- Getters ---------------------------------------------

    public function isOneTimeUse()
    {
        return (bool) $this->attributes[self::ONE_TIME_USE];
    }

    // ----------------------- Setters ---------------------------------------------

    public function setOneTimeUse($oneTimeUse)
    {
        return $this->setAttribute(self::ONE_TIME_USE, $oneTimeUse);
    }

    public function setValid($valid)
    {
        return $this->setAttribute(self::VALID, $valid);
    }

    public function setEntityType($entityType)
    {
        Type::validateType($entityType);

        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }
}
