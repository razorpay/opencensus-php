<?php

namespace RZP\Models\Workflow\Action;

use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID             = 'id';
    const WORKFLOW_ID    = 'workflow_id';
    const ADMIN_ID       = 'admin_id';
    const APPROVED       = 'approved';
    const CURRENT_LEVEL  = 'current_level';

    protected static $sign = 'w_action';

    protected $entity = 'workflow_action';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::WORKFLOW_ID,
        self::ADMIN_ID,
    ];

    protected $visible = [
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::APPROVED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::APPROVED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::APPROVED      => false,
        self::CURRENT_LEVEL => 1,
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

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function setApproved(boolean $status)
    {
        $this->setAttribute(self::APPROVED);
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
}
