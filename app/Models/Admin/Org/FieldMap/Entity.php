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

    public function getFieldsAttribute()
    {
        $fields = $this->attributes[self::FIELDS];

        if (empty($fields) === true)
        {
            return [];
        }

        return explode(',', $fields);
    }

    public function setFieldsAttribute(array $fields)
    {
        $this->attributes[self::FIELDS] = implode(',', $fields);
    }


    public function getFields() : array
    {
        return $this->getAttribute(self::FIELDS);
    }

    public function setFields(array $fields)
    {
        $this->setAttribute(self::FIELDS, $fields);
    }
}
