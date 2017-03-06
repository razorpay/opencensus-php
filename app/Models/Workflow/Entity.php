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

    const ID = 'id';
    const NAME = 'name';

    protected static $sign = 'wrkflw';

    protected $entity = 'admin';

    protected $generateIdOnCreate = false;

    protected $visible = [
        self::ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];


    protected $fillable = [
        self::NAME,
    ];

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }
}
