<?php

namespace RZP\Models\Base;

use RZP\Exception;
use RZP\Error\ErrorCode;

class PublicEntity extends UniqueIdEntity
{
    const ENTITY = 'entity';

    const PUBLIC_ID = 'public_id';

    const ADMIN = 'admin';

    const MERCHANT_ID = 'merchant_id';

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

    protected $amounts = array();

    public function toArrayPublic()
    {
        $attributes = $this->attributesToArray();

        $relations = $this->relationsToArrayPublic();

        $array = array_merge($attributes, $relations);

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

    public function toArrayReport()
    {
        $array = $this->toArrayPublic();

        unset($array[self::ENTITY]);

        foreach ($this->amounts as $key)
        {
            if (isset($array[$key]))
            {
                $array[$key] = $array[$key] / 100;
            }
        }

        $array[self::CREATED_AT] = $this->getDateInFormatDMYHMS(self::CREATED_AT);

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
            $func = 'setPublic' . studly_case($attr) . 'Attribute';

            $this->$func($array);
        }
    }

    public function relationsToArrayPublic()
    {
        $array = [];
        $public = array_flip($this->public);

        $relations = $this->relations;

        foreach ($relations as $key => $value)
        {
            $newKey = snake_case($key);
            if ($newKey !== $key)
            {
                $relations[$newKey] = $value;
                unset($relations[$key]);
            }
        }

        $publicRelations = array_intersect_key($relations, $public);

        foreach ($publicRelations as $key => $value)
        {
            if (PublicCollection::isPublicCollection($value))
            {
                $array[$key] = $value->toArrayPublicEmbedded();
            }
            else
            {
                $array[$key] = $value->toArrayPublic();
            }
        }

        return $array;
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
            if (array_key_exists($attr, $array))
            {
                $publicArray[$attr] = $array[$attr];
            }
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

    /**
     * Get dashboard link for any entity
     *
     * @return string
     */
    public function getDashboardEntityLink()
    {
        $id = $this->getId();

        $entity = $this->entity;

        // It's always needed for live mode. Not taking care of test for now.
        $url = "https://dashboard.razorpay.com/admin#/app/entity/live/$entity/$id";

        return $url;
    }

    /**
     * Get slack formatted dashboard entity link
     *
     * @param string $text
     * @return string
     */
    public function getDashboardEntityLinkForSlack($text = null)
    {
        if ($text === null)
        {
            $text = $this->getId();
        }

        $url = $this->getDashboardEntityLink();

        // In the format <link|display_text>
        return '<'. $url . '|' . $text.'>';
    }

    /**
     * Get original attributes against updated attributes
     *
     * @return array|null
     */
    public function getOriginalAttributesAgainstDirty()
    {
        $dirtyAttributes = $this->getDirty();

        if (empty($dirtyAttributes) === false)
        {
            $attributes = $this->getOriginal();

            $originalAttributes = array_intersect_key($attributes, $dirtyAttributes);

            return $originalAttributes;
        }
    }

    public static function verifyIdAndStripSign(& $id)
    {
        static::stripSignOrFail($id);

        static::verifyUniqueId($id, true);

        return $id;
    }

    public static function verifyIdAndSilentlyStripSign(& $id)
    {
        static::stripSign($id);

        static::verifyUniqueId($id, true);

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

    public static function stripSignWithoutValidation(& $id)
    {
        $delimiter = static::getDelimiter();

        $ix = strpos($id, $delimiter);

        if ($ix === false)
        {
            return false;
        }

        $id = substr($id, $ix + 1);

        return $id;
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

    public static function getSignedId($id)
    {
        return static::getIdPrefix() . $id;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getDateInFormatDMY($attribute)
    {
        $value = $this->getAttribute($attribute);

        if (empty($value))
        {
            return null;
        }

        return date('d/m/y', $value);
    }

    public function getDateInFormatDMYHMS($attribute)
    {
        $value = $this->getAttribute($attribute);

        if (empty($value))
        {
            return null;
        }

        return date('d/m/y h:i:s', $value);
    }

    /**
     * After Deleting Entity Contents are irrelevant
     * returning entity id and deleted key with value as true
     * @return array
     */
    public function toArrayDeleted()
    {
        return [static::ID => $this->getPublicId(), 'deleted' => true];
    }
}
