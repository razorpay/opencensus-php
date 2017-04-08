<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Constants\Table;
use RZP\Models\Workflow\Action\State;
use RZP\Models\Workflow\Base;

class Entity extends Base\Entity
{
    const ID             = 'id';
    const ACTION_ID      = 'action_id';
    const ADMIN_ID       = 'admin_id';
    const STEP_ID        = 'step_id';
    const APPROVED       = 'approved';

    // APPROVED column values
    const APPROVED_ENUM = [
        'approved' => 1,
        'rejected' => 0,
    ];

    // Relations
    const ADMIN = 'admin';

    protected static $sign = 'a_checker';

    protected $entity = 'action_checker';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::ADMIN_ID,
        self::ACTION_ID,
        self::STEP_ID,
        self::APPROVED,
    ];

    protected $visible = [
        self::ADMIN_ID,
        self::ADMIN,
        self::ACTION_ID,
        self::STEP_ID,
        self::APPROVED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ADMIN_ID,
        self::ADMIN,
        self::ACTION_ID,
        self::STEP_ID,
        self::APPROVED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::APPROVED => 'boolean',
    ];

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function step()
    {
        return $this->belongsTo('RZP\Models\Workflow\Step\Entity');
    }

    public function action()
    {
        return $this->belongsTo('RZP\Models\Workflow\Action\Entity');
    }

    /*
     * Getters
     */
    public function getActionId() : string
    {
        return $this->getAttribute(self::ACTION_ID);
    }

    public function getAdminId() : string
    {
        return $this->getAttribute(self::ADMIN_ID);
    }

    public function getStepId() : string
    {
        return $this->getAttribute(self::STEP_ID);
    }

    public function isApproved() : boolean
    {
        return $this->getAttribute(self::APPROVED);
    }

    public function getStatus() : string
    {
        if (empty($this->isApproved()) === true)
        {
            return;
        }

        if ($this->isApproved() === true)
        {
            return State\Entity::APPROVED;
        }
        else if ($this->isApproved() === false)
        {
            return State\Entity::REJECTED;
        }
    }
}
