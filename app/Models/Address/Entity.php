<?php

namespace RZP\Models\Address;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Constants;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ENTITY_ID             = 'entity_id';
    const ENTITY_TYPE           = 'entity_type';
    const TYPE                  = 'type';
    const PRIMARY               = 'primary';
    const LINE1                 = 'line1';
    const LINE2                 = 'line2';
    const ZIPCODE               = 'zipcode';
    const CITY                  = 'city';
    const STATE                 = 'state';
    const COUNTRY               = 'country';
    const DELETED_AT            = 'deleted_at';

    protected static $sign      = 'addr';

    protected $entity           = 'address';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::TYPE,
        self::PRIMARY,
        self::LINE1,
        self::LINE2,
        self::ZIPCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
    ];

    protected static $modifiers = [
        self::COUNTRY,
    ];

    protected $visible = [
        self::ID,
        self::LINE1,
        self::LINE2,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::TYPE,
        self::PRIMARY,
        self::ZIPCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::TYPE,
        self::PRIMARY,
        self::LINE1,
        self::LINE2,
        self::ZIPCODE,
        self::CITY,
        self::STATE,
        self::COUNTRY,
    ];

    protected $defaults = [
        self::LINE2   => null,
        self::ZIPCODE => null,
        self::PRIMARY => true,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ENTITY_ID,
    ];

    protected $casts = [
        self::PRIMARY => 'bool'
    ];

    // ----------------------------------- MODIFIERS -----------------------------------

    protected function modifyCountry(& $input)
    {
        if (empty($input[self::COUNTRY]) === true)
        {
            return;
        }

        $country = & $input[self::COUNTRY];

        $country = strtolower($country);
        // Remove dots
        $country = str_replace('.', '', $country);
        // Replace hyphens and underscores with a space
        $country = str_replace(['-', '_'], ' ', $country);
    }

    // ----------------------------------- END MODIFIERS -----------------------------------

    // ----------------------------------- GETTERS -----------------------------------

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function isPrimary()
    {
        return $this->getAttribute(self::PRIMARY);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
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

    // ----------------------------------- MUTATORS -----------------------------------

    protected function setCountryAttribute($country)
    {
        $countryCode = Constants\Country::getCountryCode($country);

        $this->attributes[self::COUNTRY] = $countryCode;
    }

    // ----------------------------------- END MUTATORS -----------------------------------

    // ----------------------------------- PUBLIC SETTERS -----------------------------------

    public function setPublicEntityIdAttribute(array & $array)
    {
        $entity = Type::getEntityClass($array[self::ENTITY_TYPE]);

        $sign = $entity::getIdPrefix();

        $array[self::ENTITY_ID] = $sign . $array[self::ENTITY_ID];
    }

    // ----------------------------------- END PUBLIC SETTERS -----------------------------------

    // ----------------------------------- RELATIONS -----------------------------------

    public function source()
    {
        $entityType = $this->getAttribute(self::ENTITY_TYPE);

        Type::validateEntityType($entityType);

        $class = Constants\Entity::getEntityClass($entityType);

        return $this->belongsTo($class, self::ENTITY_ID);
    }

    public function sourceAssociate(Base\Entity $entity)
    {
        $this->setEntityType($entity->getEntityName());

        $this->source()->associate($entity);
    }

    // ----------------------------------- END RELATIONS -----------------------------------
}