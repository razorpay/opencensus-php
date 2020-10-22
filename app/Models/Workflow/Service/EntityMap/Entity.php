<?php

namespace RZP\Models\Workflow\Service\EntityMap;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::WORKFLOW_ENTITY_MAP;
    protected $table  = Table::WORKFLOW_ENTITY_MAP;

    protected $generateIdOnCreate = true;

    const WORKFLOW_ID            = 'workflow_id';
    const MERCHANT_ID            = 'merchant_id';
    const ORG_ID                 = 'org_id';
    const ENTITY_ID              = 'entity_id';
    const CONFIG_ID              = 'config_id';
    const ENTITY_TYPE            = 'entity_type';

    // Constants
    const SOURCE                 = 'source';

    protected $fillable = [
        self::ID,
        self::WORKFLOW_ID,
        self::MERCHANT_ID,
        self::ORG_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CONFIG_ID,
    ];

    protected $visible = [
        self::ID,
        self::WORKFLOW_ID,
        self::MERCHANT_ID,
        self::ORG_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CONFIG_ID,
    ];

    protected $public = [
        self::ID,
        self::WORKFLOW_ID,
        self::MERCHANT_ID,
        self::ORG_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CONFIG_ID,
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

    public function source()
    {
        return $this->morphTo(self::SOURCE, self::ENTITY_TYPE, self::ENTITY_ID);
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

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getConfigId()
    {
        return $this->getAttribute(self::CONFIG_ID);
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

    public function setEntityId($entityId)
    {
        $this->setAttribute(self::ENTITY_ID, $entityId);
    }

    public function setEntityType($type)
    {
        $this->setAttribute(self::ENTITY_TYPE, $type);
    }

    public function setConfigId($configId)
    {
        $this->setAttribute(self::CONFIG_ID, $configId);
    }

    // ============================= END SETTERS =============================
}
