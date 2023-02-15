<?php

namespace RZP\Models\Workflow\Service\StateMap;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Admin\Org;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::WORKFLOW_STATE_MAP;
    protected $table  = Table::WORKFLOW_STATE_MAP;

    protected $generateIdOnCreate = true;

    const WORKFLOW_ID            = 'workflow_id';
    const MERCHANT_ID            = 'merchant_id';
    const ORG_ID                 = 'org_id';
    const ACTOR_TYPE_KEY         = 'actor_type_key';
    const ACTOR_TYPE_VALUE       = 'actor_type_value';
    const STATE_ID               = 'state_id';
    const STATE_NAME             = 'state_name';
    const STATUS                 = 'status';
    const GROUP_NAME             = 'group_name';
    const TYPE                   = 'type';

    const REQUEST_RULES                  = 'Rules';
    const REQUEST_ACTOR_PROPERTY_KEY     = 'ActorPropertyKey';
    const REQUEST_ACTOR_PROPERTY_VALUE   = 'ActorPropertyValue';
    const REQUEST_STATE_NAME             = 'Name';
    const REQUEST_GROUP_NAME             = 'GroupName';
    const REQUEST_STATUS                 = 'Status';
    const REQUEST_TYPE                   = 'Type';
    const REQUEST_WORKFLOW_ID            = 'WorkflowId';
    const REQUEST_STATE_ID               = 'Id';
    const REQUEST_MERCHANT_ID            = 'OwnerId';
    const REQUEST_ORG_ID                 = 'OrgId';


    protected $fillable = [
        self::ID,
        self::WORKFLOW_ID,
        self::MERCHANT_ID,
        self::ORG_ID,
        self::ACTOR_TYPE_KEY,
        self::ACTOR_TYPE_VALUE,
        self::STATE_ID,
        self::STATE_NAME,
        self::STATUS,
        self::GROUP_NAME,
        self::TYPE,
    ];

    protected $visible = [
        self::ID,
        self::WORKFLOW_ID,
        self::MERCHANT_ID,
        self::ORG_ID,
        self::ACTOR_TYPE_KEY,
        self::ACTOR_TYPE_VALUE,
        self::STATE_ID,
        self::STATE_NAME,
        self::STATUS,
        self::GROUP_NAME,
        self::TYPE,
    ];

    protected $public = [
        self::ID,
        self::WORKFLOW_ID,
        self::MERCHANT_ID,
        self::ORG_ID,
        self::ACTOR_TYPE_KEY,
        self::ACTOR_TYPE_VALUE,
        self::STATE_ID,
        self::STATE_NAME,
        self::STATUS,
        self::GROUP_NAME,
        self::TYPE,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // ============================= RELATIONS =============================

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function org()
    {
        return $this->belongsTo(Org\Entity::class);
    }

    // ============================= END RELATIONS =============================

    // ============================= GETTERS =============================

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getWorkflowId()
    {
        return $this->getAttribute(self::WORKFLOW_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getOrgId()
    {
        return $this->getAttribute(self::ORG_ID);
    }

    public function getActorTypeKey()
    {
        return $this->getAttribute(self::ACTOR_TYPE_KEY);
    }

    public function getActorTypeValue()
    {
        return $this->getAttribute(self::ACTOR_TYPE_VALUE);
    }

    public function getStateId()
    {
        return $this->getAttribute(self::STATE_ID);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getGroupName()
    {
        return $this->getAttribute(self::GROUP_NAME);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setWorkflowId($workflowId)
    {
        $this->setAttribute(self::WORKFLOW_ID, $workflowId);
    }

    public function setOrgId($orgId)
    {
        $this->setAttribute(self::ORG_ID, $orgId);
    }

    public function setActorTypeKey($actorTypeKey)
    {
        $this->setAttribute(self::ACTOR_TYPE_KEY, $actorTypeKey);
    }

    public function setActorTypeValue($actorTypeValue)
    {
        $this->setAttribute(self::ACTOR_TYPE_VALUE, $actorTypeValue);
    }

    public function setStateId($stateId)
    {
        $this->setAttribute(self::STATE_ID, $stateId);
    }

    public function setStateName($stateName)
    {
        $this->setAttribute(self::STATE_NAME, $stateName);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setGroupName($groupName)
    {
        $this->setAttribute(self::GROUP_NAME, $groupName);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    // ============================= END SETTERS =============================
}
