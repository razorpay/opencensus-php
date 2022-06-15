<?php


namespace RZP\Models\AccessControlHistoryLogs;

use RZP\Constants;
use RZP\Models\Base\PublicEntity;
use RZP\Models\AccessPolicyAuthzRolesMap;

class Entity extends PublicEntity
{
    const ID                = 'id';
    const ENTITY_TYPE       = 'entity_type';
    const ENTITY_ID         = 'entity_id';
    const MESSAGE           = 'message';
    const PREVIOUS_VALUE    = 'previous_value';
    const NEW_VALUE         = 'new_value';
    const OWNER_ID          = 'owner_id';
    const OWNER_TYPE        = 'owner_type';
    const CREATED_AT        = 'created_at';
    const CREATED_BY        = 'created_by';
    const UPDATED_AT        = 'updated_at';

    protected $entity = Constants\Table::ACCESS_CONTROL_HISTORY_LOGS;

    const ENTITY_TYPE_ROLE = 'role';

    protected static $generators = [
        self::ID,
    ];

    protected $casts = [
        self::PREVIOUS_VALUE    => 'array',
        self::NEW_VALUE         => 'array'
    ];

    protected $fillable = [
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::MESSAGE,
        self::PREVIOUS_VALUE,
        self::NEW_VALUE,
        self::OWNER_TYPE,
        self::OWNER_ID,
        self::CREATED_BY
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::MESSAGE,
        self::PREVIOUS_VALUE,
        self::NEW_VALUE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::CREATED_BY,
        self::OWNER_TYPE,
        self::OWNER_ID,
    ];

    protected $public = [
        self::ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::MESSAGE,
        self::PREVIOUS_VALUE,
        self::NEW_VALUE,
        self::OWNER_TYPE,
        self::OWNER_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::CREATED_BY,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults =[
        self::OWNER_TYPE => Constants\Entity::MERCHANT
        ];

    //Setters

    public function setEntityType(string $entityType)
    {
        return $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function setEntityId(string $entityId)
    {
        return $this->setAttribute(self::ENTITY_ID, $entityId);
    }

    public function setMessage(string $message)
    {
        return $this->setAttribute(self::MESSAGE, $message);
    }

    public function setPreviousValue(array $entity)
    {
        return $this->setAttribute(self::PREVIOUS_VALUE, json_encode($entity));
    }

    public function setNewValue(array $entity)
    {
        return $this->setAttribute(self::NEW_VALUE, json_encode($entity));
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setAttribute(self::CREATED_AT, $createdAt);
    }

    public function setCreatedBy($createdBy)
    {
        return $this->setAttribute(self::CREATED_BY, $createdBy);
    }

    //Getters

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getMessage()
    {
        return $this->getAttribute(self::MESSAGE);
    }

    public function getPreviousValue()
    {
        return $this->getAttribute(self::PREVIOUS_VALUE);
    }

    public function getNewValue()
    {
        return $this->getAttribute(self::NEW_VALUE);
    }

    public function getCreatedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getCreatedBy()
    {
        return $this->getAttribute(self::CREATED_BY);
    }
}
