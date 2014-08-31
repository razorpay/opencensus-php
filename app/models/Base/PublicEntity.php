<?php

namespace Models\Base;

use EE\Exception;
use EE\Error\ErrorCode;

class PublicEntity extends UniqueIdEntity
{
    const ENTITY = 'entity';

    protected static $sign = '';

    private static $delimiter = '-';

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

    protected $publicSetters = array('id', 'entity');

    public function toArrayPublic()
    {
        $array = $this->toArray();

        $this->setPublicAttributes($array);

        return $this->arrangePublicAttributes($array);
    }

    public function setPublicAttributes(array & $array)
    {
        foreach ($this->publicSetters as $attr)
        {
            $func = 'setPublic'.ucfirst($attr);
            $this->$func($array);
        }
    }

    public function setPublicId(array & $array)
    {
        $array[self::ID] = $this->getPublicId();
    }

    public function setPublicEntity(array & $array)
    {
        $array[self::ENTITY] = $this->entity;
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
        return static::$sign . self::$delimiter . $this->getKey();
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
        if (strpos($id, static::$sign . self::$delimiter) === false)
        {
            return false;
        }

        $len = strlen(static::$sign . self::$delimiter);

        $id = substr($id, $len);

        return true;
    }
}