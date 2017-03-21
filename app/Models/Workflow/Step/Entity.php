<?php

namespace RZP\Models\Workflow\Step;

use App;
use Hash;
use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Base\Traits\RevisionableTrait;

class Entity extends Base\PublicEntity
{
    // enable revisioning on this entity
    use RevisionableTrait;

    const ID             = 'id';
    const WORKFLOW_ID    = 'workflow_id';
    const LEVEL          = 'level';
    const ROLE_ID        = 'role_id';
    const REVIEWER_COUNT = 'reviewer_count';

    protected static $sign = 'w_step';

    protected $entity = 'workflow_step';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::LEVEL,
        self::REVIEWER_COUNT,
        self::ROLE_ID,
        self::PERMISSION_ID,
        self::WORKFLOW_ID,
    ];

    protected $visible = [
        self::LEVEL,
        self::REVIEWER_COUNT,
        self::ROLE_ID,
        self::PERMISSION_ID,
        self::WORKFLOW_ID,
    ];

    protected $public = [
        self::LEVEL,
        self::REVIEWER_COUNT,
        self::ROLE_ID,
        self::PERMISSION_ID,
        self::WORKFLOW_ID,
    ];

    protected $casts = [
        self::LEVEL => 'integer',
    ];

    protected $defaults = [
        self::LEVEL => 0,
    ];

    public function workflow()
    {
        return $this->belongsTo('RZP\Models\Workflow\Entity');
    }

    public function role()
    {
        return $this->belongsTo('RZP\Models\Admin\Role\Entity');
    }

    public function permission()
    {
        return $this->belongsTo('RZP\Models\Admin\Permission\Entity');
    }
}
