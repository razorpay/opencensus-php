<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Action\State;

class Entity extends Base\Entity
{
    const ID             = 'id';
    const TITLE          = 'title';
    const DESCRIPTION    = 'description';
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
        self::TITLE,
        self::DESCRIPTION,
        self::ORG_ID,
        self::ADMIN_ID,
        self::WORKFLOW_ID,
    ];

    protected $visible = [
        self::ID,
        self::TITLE,
        self::DESCRIPTION,
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::ORG_ID,
        self::APPROVED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::ORG_ID,
    ];

    protected $public = [
        self::ID,
        self::TITLE,
        self::DESCRIPTION,
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

    public function getCurrentLevel() : int
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

    public function getFinalState()
    {
        if (empty($this->getId()) === true)
        {
            return ;
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

    public function toArrayPublicWithAdminAndSteps()
    {
        $data = $this->toArrayPublic();

        $data['admin'] = $this->admin->toArrayPublic();

        $data['workflow_steps'] = [];

        foreach ($this->workflow->steps as $step)
        {
            $thisStep = $step->toArrayPublic();

            $thisStep['role'] = $step->role->toArrayPublic();

            $data['workflow_steps'][] = $thisStep;
        }

        return $data;
    }
}
