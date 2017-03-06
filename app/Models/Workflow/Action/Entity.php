<?php

namespace RZP\Models\Workflow\Action;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Base\Traits\RevisionableTrait;

class Entity extends Base\PublicEntity
{
    // enable revisioning on this entity
    use RevisionableTrait;

    const ID             = 'id';
    const WORKFLOW_ID    = 'workflow_id';
    const ADMIN_ID       = 'admin_id';

    protected $entity = 'step';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::WORKFLOW_ID,
        self::ADMIN_ID,
    ];

    protected $visible = [
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function workflow()
    {
        return $this->belongsTo('RZP\Models\Workflow\Entity');
    }

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }
}
