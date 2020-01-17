<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Action;

class Entity extends Base\Entity
{
    const ID           = 'id';
    const ACTION_ID    = 'action_id';
    const ADMIN_ID     = 'admin_id';
    const CHECKER_TYPE = 'checker_type';
    const CHECKER_ID   = 'checker_id';
    const STEP_ID      = 'step_id';
    const APPROVED     = 'approved';
    const USER_COMMENT = 'user_comment';

    // APPROVED column values
    const APPROVED_ENUM = [
        'approved' => 1,
        'rejected' => 0,
    ];

    // Relations
    const ADMIN = 'admin';
    const CHECKER = 'checker';

    protected static $sign = 'a_checker';

    protected $entity = 'action_checker';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::APPROVED,
        self::USER_COMMENT,
    ];

    protected $visible = [
        self::ID,
        self::ADMIN_ID,
        self::CHECKER_TYPE,
        self::CHECKER_ID,
        self::ADMIN,
        self::CHECKER,
        self::ACTION_ID,
        self::STEP_ID,
        self::APPROVED,
        self::USER_COMMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ADMIN_ID,
        self::ADMIN,
        self::CHECKER,
        self::ACTION_ID,
        self::STEP_ID,
        self::APPROVED,
        self::USER_COMMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::APPROVED => 'boolean',
    ];

    protected $publicSetters = [
        self::ID,
        self::ADMIN_ID,
        self::ACTION_ID,
        self::STEP_ID,
    ];

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function checker()
    {
        return $this->morphTo();
    }

    public function action()
    {
        return $this->belongsTo(Action\Entity::class);
    }

    public function step()
    {
        return $this->belongsTo(Step\Entity::class);
    }

    /*
     * Getters
     */

    public function getAdminId() : string
    {
        return $this->getAttribute(self::ADMIN_ID);
    }

    public function isApproved() : bool
    {
        return $this->getAttribute(self::APPROVED);
    }
}
