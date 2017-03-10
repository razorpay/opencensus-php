<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Constants\Table;
use RZP\Models\Base;

use RZP\Models\Workflow\Action\State;

class Entity extends Base\PublicEntity
{
    const ID             = 'id';
    const ACTION_ID      = 'action_id';
    const ADMIN_ID       = 'admin_id';
    const STEP           = 'step';
    const APPROVED       = 'approved';

    protected $entity = 'action_checker';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::ADMIN_ID,
        self::ACTION_ID,
        self::STEP,
    ];

    protected $visible = [
        self::ADMIN_ID,
        self::ACTION_ID,
        self::STEP,
        self::CREATED_AT,
        self::APPROVED,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ADMIN_ID,
        self::ACTION_ID,
        self::STEP,
        self::APPROVED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function action()
    {
        return $this->belongsTo('RZP\Models\Workflow\Action\Entity');
    }

    /*
     * Getters
     */
    public function getActionId()
    {
        return $this->getAttribute(self::ACTION_ID);
    }

    public function isApproved()
    {
        return $this->getAttribute(self::APPROVED);
    }

    public function getCheckerStatusOnAction()
    {
        if (empty($this->isApproved()) === true)
        {
            return State::UNDER_REVIEW;
        }

        if ($this->isApproved() === true)
        {
            return State::APPROVED;
        }

        return State::REJECTED;
    }
}
