<?php

namespace RZP\Models\Address;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ENTITY_ID             = 'entity_id';
    const ENTITY_TYPE           = 'entity_type';
    const ADDRESS_TYPE          = 'address_type';
    const PRIMARY               = 'primary';
    const LINE1                 = 'line1';
    const LINE2                 = 'line2';
    const PINCODE               = 'pincode';
    const CITY                  = 'city';
    const STATE                 = 'state';
    const COUNTRY               = 'country';
    const DELETED_AT            = 'deleted_at';

    protected static $sign      = 'addr';

    protected $entity           = 'address';

    protected $table            = Table::ADDRESS;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::ADDRESS_TYPE,
        self::PRIMARY,
        self::LINE1,
        self::LINE2,
        self::PINCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
    ];

    protected $visible = [
        self::ID,
        self::LINE1,
        self::LINE2,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::ADDRESS_TYPE,
        self::PRIMARY,
        self::PINCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::ADDRESS_TYPE,
        self::PRIMARY,
        self::LINE1,
        self::LINE2,
        self::PINCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
    ];

    protected $defaults = [
        self::LINE2         => null,
        self::PINCODE       => null,
        self::PRIMARY       => true,
    ];

    protected $casts = [
        self::PRIMARY => 'bool'
    ];

    // ----------------------------------- GETTERS -----------------------------------

    public function getAddressType()
    {
        return $this->getAttribute(self::ADDRESS_TYPE);
    }

    public function isPrimary()
    {
        return $this->getAttribute(self::PRIMARY);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    // ----------------------------------- END GETTERS -----------------------------------

    // ----------------------------------- SETTERS -----------------------------------

    public function setEntityType($entityType)
    {
        Type::validateEntityType($entityType);

        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function setPrimary($primary)
    {
        $this->setAttribute(self::PRIMARY, $primary);
    }

    // ----------------------------------- END SETTERS -----------------------------------

    // ----------------------------------- RELATIONS -----------------------------------

    public function source()
    {
        $entityType = $this->getAttribute(self::ENTITY_TYPE);

        Type::validateEntityType($entityType);

        $class = 'RZP\\Models\\';

        $class .= ucfirst($entityType) . '\\' . 'Entity';

        return $this->belongsTo($class, self::ENTITY_ID);
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity', self::ENTITY_ID);
    }

    // ----------------------------------- END RELATIONS -----------------------------------
}