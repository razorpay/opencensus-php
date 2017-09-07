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
    const OP_TYPE        = 'op_type';

    const WORKFLOW       = 'workflow';

    const OP_TYPE_AND    = 'and';
    const OP_TYPE_OR     = 'or';

    protected static $sign = 'w_step';

    protected $entity = 'workflow_step';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::WORKFLOW_ID,
        self::LEVEL,
        self::ROLE_ID,
        self::REVIEWER_COUNT,
        self::OP_TYPE,
    ];

    protected $visible = [
        self::ID,
        self::WORKFLOW_ID,
        self::LEVEL,
        self::ROLE_ID,
        self::OP_TYPE,
        self::REVIEWER_COUNT,
        'role',
    ];

    protected $public = [
        self::ID,
        self::WORKFLOW_ID,
        self::LEVEL,
        self::ROLE_ID,
        self::OP_TYPE,
        self::REVIEWER_COUNT,
        'role',
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
        self::LEVEL   => 1,
        self::OP_TYPE => self::OP_TYPE_AND,
    ];

    public function workflow()
    {
        return $this->belongsTo('RZP\Models\Workflow\Entity');
    }

    public function role()
    {
        return $this->belongsTo('RZP\Models\Admin\Role\Entity');
    }

    public function getOpType()
    {
        return $this->getAttribute(self::OP_TYPE);
    }

    public function getLevel()
    {
        return $this->getAttribute(self::LEVEL);
    }

    public function getWorkflowId()
    {
        return $this->getAttribute(self::WORKFLOW_ID);
    }

    public function getReviewerCount()
    {
        return $this->getAttribute(self::REVIEWER_COUNT);
    }
}
