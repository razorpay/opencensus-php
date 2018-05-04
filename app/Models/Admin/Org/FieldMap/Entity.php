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
    const ENTITY_NAME = 'entity_name';
    const FIELDS      = 'fields';

    protected $entity = 'org_field_map';

    protected static $sign = 'ofm';

    protected $allowHardDelete = true;

    protected $fillable = [
        self::ORG_ID,
        self::ENTITY_NAME,
        self::FIELDS,
    ];

    protected $visible = [
        self::ID,
        self::ORG_ID,
        self::ENTITY_NAME,
        self::FIELDS,
    ];

    protected $public = [
        self::ID,
        self::ORG_ID,
        self::ENTITY_NAME,
        self::FIELDS,
    ];

    public function org()
    {
        return $this->belongsTo('RZP\Models\Org\Entity');
    }

    public function setFieldsAttribute(array $fields)
    {
        $this->attributes[self::FIELDS] = implode(',', $fields);
    }

    public function setFields(array $fields)
    {
        $this->setAttribute(self::FIELDS, $fields);
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

    public function getFields() : array
    {
        return $this->getAttribute(self::FIELDS);
    }

    public function getNameOfEntity() : string
    {
        return $this->getAttribute(self::ENTITY_NAME);
    }

    public function getInputFields() : array
    {
        return $this->fillable;
    }

    public function getOrgId() : string
    {
        return $this->getAttribute(self::ORG_ID);
    }
}
