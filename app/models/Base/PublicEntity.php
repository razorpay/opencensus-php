<?php

namespace Models\Base;

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

    public function toArrayPublic()
    {
        $array = $this->toArray();

        $array[self::ID ] = $this->getPublicId();

        $array['entity'] = $this->entity;

        $publicArray = array();

        foreach ($this->public as $attr)
        {
            $publicArray[$attr] = $array[$attr];
        }

        return $publicArray;
    }

    public function getPublicId()
    {
        return static::$sign . self::$delimiter . $this->getKey();
    }


    public static function verifyIdAndStripSign(& $id)
    {
        self::stripSignOrFail($id);

        UniqueIdEntity::verifyUniqueId($id, true);
    }

    protected static function stripSignOrFail(& $id)
    {
        if (strpos($id, static::$sign . self::$delimiter) === false)
        {
            throw new Exception\BadRequestException(null, ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $len = strlen(static::$sign . self::$delimiter);

        $id = substr($id, $len);
    }
}