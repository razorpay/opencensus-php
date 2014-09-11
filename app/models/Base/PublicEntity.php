<?php

namespace Models\Base;

use EE\Exception;
use EE\Error\ErrorCode;

class PublicEntity extends UniqueIdEntity
{
    const ENTITY = 'entity';

    protected static $sign = '';

    protected static $delimiter = '-';

    /**
     * For an entity which is being exposed outside,
     * it is important to ensure that all the attributes
     * being sent are in the correct order, ie, id comes
     * first and then others, etc.
     *
     * The attributes defined in the array should be
     * in the order expected to be sent out
     *
     * @var array
     */
    protected $public = array();

    protected $publicSetters = array(self::ID, self::ENTITY);

    public function toArrayPublic()
    {
        $array = $this->toArray();

        $this->setPublicAttributes($array);

        return $this->arrangePublicAttributes($array);
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = array())
    {
        return new PublicCollection($models);
    }

    public function setPublicAttributes(array & $array)
    {
        foreach ($this->publicSetters as $attr)
        {
            $func = 'setPublic'.studly_case($attr).'Attribute';

            $this->$func($array);
        }
    }

    public function setPublicIdAttribute(array & $array)
    {
        $array[static::ID] = $this->getPublicId();
    }

    public function setPublicEntityAttribute(array & $array)
    {
        $array[static::ENTITY] = $this->entity;
    }

    public function arrangePublicAttributes(array $array)
    {
        $publicArray = array();

        foreach ($this->public as $attr)
        {
            $publicArray[$attr] = isset($array[$attr]) ? $array[$attr] : null;
        }

        return $publicArray;
    }

    public function getPublicId()
    {
        return static::$sign . static::$delimiter . $this->getKey();
    }

    public static function verifyIdAndStripSign(& $id)
    {
        static::stripSignOrFail($id);

        UniqueIdEntity::verifyUniqueId($id, true);
    }

    protected static function stripSignOrFail(& $id)
    {
        if (static::stripSign($id) === false)
        {
            throw new Exception\BadRequestException(null, ErrorCode::BAD_REQUEST_INVALID_ID);
        }
    }

    protected static function stripSign(& $id)
    {
        if (strpos($id, static::$sign . static::$delimiter) === false)
        {
            return false;
        }

        $len = strlen(static::$sign . static::$delimiter);

        $id = substr($id, $len);

        return true;
    }

    public static function getSign()
    {
        return static::$sign;
    }
}