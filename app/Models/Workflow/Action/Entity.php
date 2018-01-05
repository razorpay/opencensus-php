<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\State;
use RZP\Models\Comment;
use RZP\Models\Workflow\Base;


class Entity extends Base\Entity
{
    const ID             = 'id';
    const ENTITY_ID      = 'entity_id';
    const ENTITY_NAME    = 'entity_name';
    const TITLE          = 'title';
    const DESCRIPTION    = 'description';
    const WORKFLOW_ID    = 'workflow_id';
    const PERMISSION_ID  = 'permission_id';
    const ADMIN_ID       = 'admin_id';
    const ORG_ID         = 'org_id';
    const APPROVED       = 'approved';
    const STATE          = 'state';
    const CURRENT_LEVEL  = 'current_level';
    const DIFFER         = 'differ';

    // Relations
    const WORKFLOW       = 'workflow';
    const ADMIN          = 'admin';
    const PERMISSION     = 'permission';
    const ACTION_ID      = 'action_id';

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
        self::ORG_ID,
        self::ADMIN_ID,
        self::WORKFLOW_ID,
        self::PERMISSION_ID,
        self::STATE,
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
        self::ADMIN_ID,
        self::ADMIN,
        self::ORG_ID,
        self::APPROVED,
        self::CURRENT_LEVEL,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PERMISSION_NAME,
        self::PERMISSION_DESCRIPTION,
    ];

    protected $publicSetters = [
        self::ID,
        self::WORKFLOW_ID,
        self::PERMISSION_ID,
        self::ADMIN_ID,
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
        self::ADMIN_ID,
        self::ADMIN,
        self::ORG_ID,
        self::APPROVED,
        self::CURRENT_LEVEL,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PERMISSION_NAME,
        self::PERMISSION_DESCRIPTION,
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

    // public function state()
    // {
    //     return $this->hasMany('RZP\Models\Workflow\Action\State\Entity', self::ACTION_ID);
    // }

    // public function org()
    // {
    //     return $this->belongsTo('RZP\Models\Admin\Org\Entity');
    // }

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
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

    public function getAdminId()
    {
        return $this->getAttribute(self::ADMIN_ID);
    }

    public function toArrayPublicWithAdminAndSteps()
    {
        $data = $this->toArrayPublic();

        $data['admin'] = $this->admin()->withTrashed()->first()->toArrayPublic();

        $data['workflow_steps'] = [];

        $workflow = $this->workflow()->withTrashed()->first();

        foreach ($workflow->steps as $step)
        {
            $thisStep = $step->toArrayPublic();

            $thisStep['role'] = $step->role->toArrayPublic();

            $data['workflow_steps'][] = $thisStep;
        }

        unset($data['workflow']['steps']);

        return $data;
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
