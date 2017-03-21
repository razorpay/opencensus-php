<?php

namespace RZP\Models\Workflow;

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

    const ID            = 'id';
    const NAME          = 'name';
    const ORG_ID        = 'org_id';
    const PERMISSION_ID = 'permission_id';

    protected static $sign = 'workflow';

    protected $entity = 'workflow';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::ID,
        self::NAME,
        self::ORG_ID,
        self::PERMISSION_ID,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::ORG_ID,
        self::PERMISSION_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::ORG_ID,
        self::PERMISSION_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function org()
    {
        return $this->belongsTo('RZP\Models\Admin\Org\Entity');
    }

    public function permission()
    {
        return $this->belongsTo('RZP\Models\Admin\Permission\Entity');
    }

    public function getOrgId()
    {
        return $this->getAttribute(self::ORG_ID);
    }
}
