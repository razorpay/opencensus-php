<?php

namespace Models\Base;

use EE\Exception;
use EE\Error\ErrorCode;

class PublicEntity extends UniqueIdEntity
{
    const ENTITY = 'entity';

    const PUBLIC_ID = 'public_id';

    const ADMIN = 'admin';

    protected static $sign = '';

    protected static $delimiter = '_';

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

    public function toArrayAdmin()
    {
        $array = $this->toArray();

        $this->setPublicAttributes($array);

        $array[static::ADMIN] = true;

        return $array;
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
        return static::$sign . static::getDelimiter() . $this->getKey();
    }

    public function getPublicIdAttribute()
    {
        return $this->getPublicId();
    }

    public static function verifyIdAndStripSign(& $id)
    {
        static::stripSignOrFail($id);

        UniqueIdEntity::verifyUniqueId($id, true);

        return $id;
    }

    protected static function stripSignOrFail(& $id)
    {
        if (static::stripSign($id) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }
    }

    protected static function stripSign(& $id)
    {
        if (static::getSign() === '')
        {
            return true;
        }

        $delimiter = static::getDelimiter();

        if (strpos($id, static::$sign . $delimiter) === false)
        {
            return false;
        }

        $len = strlen(static::$sign . $delimiter);

        $id = substr($id, $len);

        return true;
    }

    public static function getSign()
    {
        return static::$sign;
    }

    public static function getIdPrefix()
    {
        return static::$sign . static::getDelimiter();
    }

    public static function getDelimiter()
    {
        if (static::$sign === '')
            return '';

        return static::$delimiter;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getMerchantId()
    {
        return $this->getAttribute(static::MERCHANT_ID);
    }
}