<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\State;
use RZP\Models\Comment;
use RZP\Models\Workflow;
use RZP\Models\Admin\Org;
use Conner\Tagging\Taggable;
use RZP\Models\Workflow\Base;
use RZP\Models\Admin\Permission;

/**
 * Class Entity
 *
 * @package RZP\Models\Workflow\Action
 *
 * @property Permission\Entity $permission
 * @property Workflow\Entity   $workflow
 */
class Entity extends Base\Entity
{
    use Taggable;

    const ID                    = 'id';
    const ENTITY_ID             = 'entity_id';
    const ENTITY_NAME           = 'entity_name';
    const TITLE                 = 'title';
    const DESCRIPTION           = 'description';
    const WORKFLOW_ID           = 'workflow_id';
    const PERMISSION_ID         = 'permission_id';
    const STATE_CHANGER_ID      = 'state_changer_id';
    const STATE_CHANGER_TYPE    = 'state_changer_type';
    const STATE_CHANGER_ROLE    = 'state_changer_role';
    const STATE_CHANGER_ROLE_ID = 'state_changer_role_id';
    const MAKER_ID              = 'maker_id';
    const MAKER_TYPE            = 'maker_type';
    const ORG_ID                = 'org_id';
    const APPROVED              = 'approved';
    const STATE                 = 'state';
    const CURRENT_LEVEL         = 'current_level';
    const DIFFER                = 'differ';
    const TAGS                  = 'tags';
    const TAGGED                = 'tagged';
    const OWNER_ID              = 'owner_id';
    const OWNER                 = 'owner';
    const ASSIGNED_AT           = 'assigned_at';

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
        self::STATE_CHANGER_TYPE,
        self::STATE_CHANGER_ROLE_ID,
        self::ASSIGNED_AT,
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
        self::STATE_CHANGER_TYPE,
        self::STATE_CHANGER_ROLE,
        self::TAGGED,
        self::OWNER_ID,
        self::OWNER,
        self::ASSIGNED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::WORKFLOW_ID,
        self::PERMISSION_ID,
        self::STATE_CHANGER_ID,
        self::MAKER_ID,
        self::ORG_ID,
        self::TAGGED,
        self::OWNER_ID,
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
        self::OWNER_ID,
        self::APPROVED,
        self::CURRENT_LEVEL,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PERMISSION_NAME,
        self::PERMISSION_DESCRIPTION,
        self::STATE_CHANGER_ID,
        self::STATE_CHANGER_TYPE,
        self::STATE_CHANGER_ROLE,
        self::TAGGED,
        self::OWNER,
        self::ASSIGNED_AT,
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

    protected $embeddedRelations = [
        self::TAGGED
    ];

    public function setPublicTaggedAttribute(& $attributes)
    {
        if (empty($attributes[self::TAGGED]) == false)
        {
            $tags = $attributes[self::TAGGED]->toArray();

            $tags = array_fetch($tags, 'tag_slug');

            $attributes[self::TAGGED] = $tags;
        }
    }

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

    public function owner()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function setAssignedAt()
    {
        $this->setAttribute(self::ASSIGNED_AT, time());
    }

    public function maker()
    {
        return $this->morphTo();
    }

    public function stateChanger()
    {
        return $this->morphTo();
    }

    public function stateChangerRole()
    {
        return $this->belongsTo('RZP\Models\Admin\Role\Entity');
    }

    public function setCurrentLevel(int $level)
    {
        $this->setAttribute(self::CURRENT_LEVEL, $level);
    }

    public function getOwnerId()
    {
        return $this->getAttribute(self::OWNER_ID);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getWorkflowEntityName()
    {
        return $this->getAttribute(self::ENTITY_NAME);
    }

    public function getCurrentLevel() : int
    {
        return $this->getAttribute(self::CURRENT_LEVEL);
    }

    public function getStateChangerId() : string
    {
        return $this->getAttribute(self::STATE_CHANGER_ID);
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

    public function isRejected()
    {
        $state = $this->getState();

        return ($state === State\Name::REJECTED);
    }

    public function getMakerId()
    {
        return $this->getAttribute(self::MAKER_ID);
    }

    public function getMakerType()
    {
        return $this->getAttribute(self::MAKER_TYPE);
    }

    public function getUpdatedAt()
    {
        return $this->getAttribute(self::UPDATED_AT);
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
