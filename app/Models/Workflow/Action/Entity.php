<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\State;
use RZP\Models\Comment;
use RZP\Models\Admin\Org;
use RZP\Models\Workflow\Base;


class Entity extends Base\Entity
{
    const ID                    = 'id';
    const ENTITY_ID             = 'entity_id';
    const ENTITY_NAME           = 'entity_name';
    const TITLE                 = 'title';
    const DESCRIPTION           = 'description';
    const WORKFLOW_ID           = 'workflow_id';
    const PERMISSION_ID         = 'permission_id';
    const STATE_CHANGER_ID      = 'state_changer_id';
    const STATE_CHANGER_ROLE    = 'state_changer_role';
    const STATE_CHANGER_ROLE_ID = 'state_changer_role_id';
    const MAKER_ID              = 'maker_id';
    const MAKER_TYPE            = 'maker_type';
    const ORG_ID                = 'org_id';
    const APPROVED              = 'approved';
    const STATE                 = 'state';
    const CURRENT_LEVEL         = 'current_level';
    const DIFFER                = 'differ';

    // Relations
    const WORKFLOW      = 'workflow';
    const STATE_CHANGER = 'state_changer';
    const PERMISSION    = 'permission';
    const ACTION_ID     = 'action_id';
    const MAKER         = 'maker';

    // Public fields from relations
    const PERMISSION_NAME           = 'permission_name';
    const PERMISSION_DESCRIPTION    = 'permission_description';

    protected static $sign = 'w_action';

    protected $entity = 'workflow_action';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::ENTITY_ID,
        self::ENTITY_NAME,
        self::TITLE,
        self::DESCRIPTION,
        self::APPROVED,
        self::STATE,
        self::STATE_CHANGER_ID,
        self::STATE_CHANGER_ROLE_ID
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_NAME,
        self::TITLE,
        self::DESCRIPTION,
        self::WORKFLOW_ID,
        self::WORKFLOW,
        self::PERMISSION_ID,
        self::PERMISSION,
        self::STATE,
        self::MAKER_ID,
        self::MAKER_TYPE,
        self::MAKER,
        self::STATE_CHANGER,
        self::ORG_ID,
        self::APPROVED,
        self::CURRENT_LEVEL,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PERMISSION_NAME,
        self::PERMISSION_DESCRIPTION,
        self::STATE_CHANGER_ID,
        self::STATE_CHANGER_ROLE,
    ];

    protected $publicSetters = [
        self::ID,
        self::WORKFLOW_ID,
        self::PERMISSION_ID,
        self::STATE_CHANGER_ID,
        self::MAKER_ID,
        self::ORG_ID,
    ];

    protected $public = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_NAME,
        self::TITLE,
        self::DESCRIPTION,
        self::WORKFLOW_ID,
        self::WORKFLOW,
        self::PERMISSION_ID,
        self::PERMISSION,
        self::STATE,
        self::MAKER_ID,
        self::MAKER_TYPE,
        self::MAKER,
        self::STATE_CHANGER,
        self::ORG_ID,
        self::APPROVED,
        self::CURRENT_LEVEL,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PERMISSION_NAME,
        self::PERMISSION_DESCRIPTION,
        self::STATE_CHANGER_ID,
        self::STATE_CHANGER_ROLE,
    ];

    protected $defaults = [
        self::APPROVED      => false,
        self::CURRENT_LEVEL => 1,
        self::STATE         => State\Name::OPEN,
    ];

    protected $casts = [
        self::APPROVED      => 'boolean',
        self::CURRENT_LEVEL => 'integer',
    ];

    public function org()
    {
        return $this->belongsTo(Org\Entity::class);
    }

    public function workflow()
    {
        return $this->belongsTo('RZP\Models\Workflow\Entity');
    }

    public function permission()
    {
        return $this->belongsTo('RZP\Models\Admin\Permission\Entity');
    }

    public function comments()
    {
        return $this->morphMany(Comment\Entity::class, 'entity');
    }

    public function maker()
    {
        return $this->morphTo();
    }

    public function stateChanger()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function stateChangerRole()
    {
        return $this->belongsTo('RZP\Models\Admin\Role\Entity');
    }

    public function setCurrentLevel(int $level)
    {
        $this->setAttribute(self::CURRENT_LEVEL, $level);
    }

    public function getCurrentLevel() : int
    {
        return $this->getAttribute(self::CURRENT_LEVEL);
    }

    public function getWorkflowId() : string
    {
        return $this->getAttribute(self::WORKFLOW_ID);
    }

    public function getApproved() : bool
    {
        return $this->getAttribute(self::APPROVED);
    }

    public function getState()
    {
        return $this->getAttribute(self::STATE);
    }

    public function isExecuted()
    {
        $state = $this->getState();

        return ($state === State\Name::EXECUTED);
    }

    public function getMakerId()
    {
        return $this->getAttribute(self::MAKER_ID);
    }

    public function getMakerType()
    {
        return $this->getAttribute(self::MAKER_TYPE);
    }

    public function isOpen()
    {
        $state = $this->getState();

        return (in_array($state, State\Name::OPEN_ACTION_STATES, true) === true);
    }

    public function isClosed(): bool
    {
        $state = $this->getState();

        return (in_array($state, State\Name::CLOSED_ACTION_STATES, true) === true);
    }
}
