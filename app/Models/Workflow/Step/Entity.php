<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Workflow\Base;

class Entity extends Base\Entity
{
    const ID             = 'id';
    const WORKFLOW_ID    = 'workflow_id';
    const LEVEL          = 'level';
    const ROLE_ID        = 'role_id';
    const REVIEWER_COUNT = 'reviewer_count';

    const WORKFLOW       = 'workflow';

    protected static $sign = 'w_step';

    protected $entity = 'workflow_step';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::WORKFLOW_ID,
        self::LEVEL,
        self::ROLE_ID,
        self::REVIEWER_COUNT,
    ];

    protected $visible = [
        self::ID,
        self::WORKFLOW_ID,
        self::LEVEL,
        self::ROLE_ID,
        self::REVIEWER_COUNT,
    ];

    protected $public = [
        self::ID,
        self::WORKFLOW_ID,
        self::LEVEL,
        self::ROLE_ID,
        self::REVIEWER_COUNT,
    ];

    protected $publicSetters = [
        self::ID,
        self::WORKFLOW_ID,
        self::ROLE_ID,
    ];

    protected $casts = [
        self::LEVEL => 'integer',
    ];

    protected $defaults = [
        self::LEVEL => 1,
    ];

    public function workflow()
    {
        return $this->belongsTo('RZP\Models\Workflow\Entity');
    }

    public function role()
    {
        return $this->belongsTo('RZP\Models\Admin\Role\Entity');
    }

    public function getLevel()
    {
        return $this->getAttribute(self::LEVEL);
    }

    public function getWorkflowId()
    {
        return $this->getAttribute(self::WORKFLOW_ID);
    }
}
