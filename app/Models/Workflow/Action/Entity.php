<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Action\State;

class Entity extends Base\Entity
{
    const ID             = 'id';
    const WORKFLOW_ID    = 'workflow_id';
    const ADMIN_ID       = 'admin_id';
    const ORG_ID         = 'org_id';
    const APPROVED       = 'approved';
    const STATE          = 'state';
    const CURRENT_LEVEL  = 'current_level';

    const DIFFER         = 'differ';

    protected static $sign = 'w_action';

    protected $entity = 'workflow_action';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::ORG_ID,
        self::ADMIN_ID,
        self::WORKFLOW_ID,
    ];

    protected $visible = [
        self::ID,
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::ORG_ID,
        self::APPROVED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::ORG_ID,
        self::APPROVED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::APPROVED      => false,
        self::CURRENT_LEVEL => 1,
        self::STATE         => State\Entity::OPEN,
    ];

    protected $casts = [
        self::APPROVED      => 'boolean',
        self::CURRENT_LEVEL => 'integer',
    ];

    public function workflow()
    {
        return $this->belongsTo('RZP\Models\Workflow\Entity');
    }

    public function state()
    {
        return $this->hasMany('RZP\Models\Workflow\Action\State\Entity');
    }

    public function org()
    {
        return $this->belongsTo('RZP\Models\Admin\Org\Entity');
    }

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function setApproved(boolean $status)
    {
        $this->setAttribute(self::APPROVED);
    }

    public function setCurrentLevel(int $level)
    {
        $this->setAttribute(self::CURRENT_LEVEL, $level);
    }

    public function getCurrentLevel() : integer
    {
        return $this->getAttribute(self::CURRENT_LEVEL);
    }

    public function getWorkflowId() : string
    {
        return $this->getAttribute(self::WORKFLOW_ID);
    }

    public function getApproved() : boolean
    {
        return $this->getAttribute(self::APPROVED);
    }

    public function getFinalState() : string
    {
        if (empty($this->getId()) === true)
        {
            return;
        }

        $state = $this->repo->action_state->getLatestState($this->getId());

        return $state;
    }

    public function isValid() : boolean
    {
        // Get the final state in the automata and
        // check if the action is still open
        $state = $this->getFinalState();

        if ((empty($state) === true) and
            ($state->isClosedState() === true))
        {
            return false;
        }

        return true;
    }

    public function incrementCurrentLevel()
    {
        $this->increment(self::CURRENT_LEVEL);
    }

    public function getAdminId()
    {
        return $this->getAttribute(self::ADMIN_ID);
    }
}
