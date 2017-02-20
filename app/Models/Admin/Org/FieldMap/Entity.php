<?php

namespace RZP\Models\Admin\Org\FieldMap;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Admin\Admin;

class Entity extends Base\PublicEntity
{
    const ID          = 'id';
    const ORG_ID      = 'org_id';
    const ENTITY      = 'entity';
    const FIELDS      = 'fields';

    protected $entity = 'org_fieldmap';

    protected $fillable = [
        self::ORG_ID,
        self::ENTITY,
        self::FIELDS,
    ];

    protected $visible = [
        self::ID,
        self::ORG_ID,
        self::ENTITY,
        self::FIELDS,
    ];

    protected $public = [
        self::ID,
        self::ORG_ID,
        self::ENTITY,
        self::FIELDS,
    ];

    public function org()
    {
        return $this->hasOne('RZP\Models\Org\Entity');
    }
}
