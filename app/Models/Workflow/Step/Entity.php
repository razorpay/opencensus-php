<?php

namespace RZP\Models\Workflow\Step;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Constants\Mode;
use RZP\Models\Workflow\Base;

class Entity extends Base\Entity
{
    use SoftDeletes;

    const ID             = 'id';
    const WORKFLOW_ID    = 'workflow_id';
    const LEVEL          = 'level';
    const ROLE_ID        = 'role_id';
    const REVIEWER_COUNT = 'reviewer_count';
    const OP_TYPE        = 'op_type';

    //
    const WORKFLOW       = 'workflow';
    const STEP_ID        = 'step_id';

    const OP_TYPE_AND    = 'and';
    const OP_TYPE_OR     = 'or';

    protected static $sign = 'w_step';

    protected $entity = 'workflow_step';

    protected $generateIdOnCreate = false;

    protected static $isCacEnabled = false;

    protected $fillable = [
        self::LEVEL,
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
        'checkers',
    ];

    protected $public = [
        self::ID,
        self::WORKFLOW_ID,
        self::LEVEL,
        self::ROLE_ID,
        self::OP_TYPE,
        self::REVIEWER_COUNT,
        'role',
        'checkers',
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
        if (self::getCacStatus() === true)
        {
            return $this->belongsTo('RZP\Models\Roles\Entity');
        }
        else
        {
            return $this->belongsTo('RZP\Models\Admin\Role\Entity');
        }
    }

    public function checkers()
    {
        return $this->hasMany('RZP\Models\Workflow\Action\Checker\Entity', self::STEP_ID);
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

    public static function getCacStatus()
    {
        return self::$isCacEnabled;
    }

    public static function setCacStatus($isCacEnabled)
    {
        return self::$isCacEnabled = $isCacEnabled;
    }
}
