<?php

namespace RZP\Models\Base;

use RZP\Exception;
use RZP\Error\ErrorCode;

use RZP\Models\Currency;

class PublicEntity extends UniqueIdEntity
{
    const ENTITY                = 'entity';

    const PUBLIC_ID             = 'public_id';

    const ADMIN                 = 'admin';

    const MERCHANT_ID           = 'merchant_id';

    /**
     * General constant used as key for hold of collection of ids
     * in various cases.
    */
    const IDS                   = 'ids';

    protected static $sign      = '';

    protected static $delimiter = '_';

    protected $hiddenInReport   = [];

    /**
     * Fields which will get formatted as amount (e.g. 1.01) in reports
     *
     * @var array
     */
    protected $amounts          = [];

    /**
     * Usage:
     * - Base/EloquentEx.php: to serialize attributes with $dates fields casted to int,
     * - Base/PublicEntity.php: formatDateFieldsForReport(): to format $dates fields
     *   converted to a uniform string format across reports.
     *
     * Also refer Base/Entity.php::getDates().
     *
     * @var array
     */
    protected $dates            = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

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
    protected $public           = [];

    /**
     * Fields exposed to hosted page(invoice, subscriptions etc)
     * where there would mostly be no authentication.
     *
     * @var array
     */
    protected $hosted           = [];

    protected $publicSetters    = [
        self::ID,
        self::ENTITY,
    ];

    protected $embeddedRelations = [];

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

    /**
     * When we are fetching diff for other entities while showing relations,
     * this will help us to fetch data that is relevant to be shown in diff
     */
    public function toArrayDiff()
    {
        $attributes = $this->attributesToArray();

        $this->setPublicAttributes($attributes);

        return $this->arrangeDiffAttributes($attributes);
    }

    public function toArrayReport()
    {
        $array = $this->toArrayPublic();

        unset($array[self::ENTITY]);

        // Remove fields hidden in reports

        foreach ($this->getHiddenInReport() as $key)
        {
            unset($array[$key]);
        }

        $this->formatAmountFieldsForReport($array);

        $this->formatDateFieldsForReport($array);

        return $array;
    }

    protected function formatAmountFieldsForReport(array & $report)
    {
        foreach ($this->amounts as $key)
        {
            if (isset($report[$key]) === true)
            {
                $report[$key] = $report[$key] / 100;
            }
        }
    }

    protected function formatDateFieldsForReport(array & $report)
    {
        foreach ($this->dates as $key)
        {
            //
            // Adding a is_numeric check here because we want
            // to format the dates only if they are in epoch
            // format and not in some other date format already.
            //
            // For example: settled_on of settlements and payouts
            // is formatted to `d/m/Y` in accessors. We don't
            // have to format anything there for the report.
            //
            if ((isset($report[$key]) === true) and
                (is_numeric($report[$key]) === true))
            {
                $report[$key] = $this->getDateInFormatDMYHMS($key);
            }
        }
    }

    /**
     * Returns attributes to be used in public views.
     * E.g. Invoice hosted page, Subscription pages etc.
     *
     * @return array
     */
    public function toArrayHosted()
    {
        $attributes = $this->toArrayPublic();

        return array_only($attributes, $this->hosted);
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
        $public = array_flip($this->public);

        $relations = $this->relations;

        // Snake case relation's keys

        foreach ($relations as $key => $value)
        {
            $snakeCaseKey = snake_case($key);

            if ($snakeCaseKey !== $key)
            {
                $relations[$snakeCaseKey] = $value;

                unset($relations[$key]);
            }
        }

        $publicRelations = array_intersect_key($relations, $public);

        $array = [];

        foreach ($publicRelations as $key => $value)
        {
            if (PublicCollection::isPublicCollection($value) === true)
            {
                if ($this->isRelationEmbeddedInResponse($key) === true)
                {
                    $array[$key] = $value->toArrayPublicEmbedded();
                }
                else
                {
                    $array[$key] = $value->toArrayPublic();
                }
            }
            else if (static::isPublicEntity($value) === true)
            {
                $array[$key] = $value->toArrayPublic();
            }
            else
            {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    public function isRelationEmbeddedInResponse(string $key)
    {
        return in_array($key, $this->embeddedRelations);
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

    protected function arrangeDiffAttributes(array $attributes)
    {
        $diffArray = [];

        foreach ($this->diff as $attr)
        {
            if (array_key_exists($attr, $attributes))
            {
                $diffArray[$attr] = $attributes[$attr];
            }
        }

        return $diffArray;
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

    public static function verifyIdAndStripSignMultiple(array & $ids)
    {
        $newIds = array_map(function(&$id)
        {
            return static::verifyIdAndStripSign($id);
        }, $ids);

        $ids = $newIds;

        return $newIds;
    }

    public static function verifyIdAndSilentlyStripSignMultiple(array & $ids)
    {
        $newIds = array_map(function(&$id)
        {
            return static::verifyIdAndSilentlyStripSign($id);
        }, $ids);

        $ids = $newIds;

        return $newIds;
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

    public static function stripDefaultSign($id)
    {
        //created this function to strip of default public sign's
        $delimiter = static::$delimiter;

        return last(explode($delimiter, $id));
    }

    /**
     * Returns id with the sign prefix attached.
     */
    public static function getSignedId($id)
    {
        return static::getIdPrefix() . $id;
    }

    public static function getSignedIdMultiple(array & $ids)
    {
        $newIds = array_map(function(& $id)
        {
            return static::getSignedId($id);
        }, $ids);

        $ids = $newIds;
    }

    /**
     * Returns id with the sign prefix attached.
     * However, if the value is null, then simply return null.
     *
     * @param $id
     *
     * @return null|string
     */
    public static function getSignedIdOrNull($id)
    {
        return $id ? static::getSignedId($id) : null;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getHiddenInReport()
    {
        return $this->hiddenInReport;
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getDateInFormatDMY($attribute)
    {
        return $this->getDateInFormat($attribute, 'd/m/y');
    }

    public function getDateInFormat($attribute, $format)
    {
        $value = $this->getAttribute($attribute);

        if (empty($value))
        {
            return null;
        }

        return date($format, $value);
    }

    public function getDateInFormatDMYHMS($attribute)
    {
        return $this->getDateInFormat($attribute, 'd/m/y H:i:s');
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

    public function getPublicAttributes() : array
    {
        if (isset($this->public) === false)
        {
            return [];
        }
        return array_keys(array_flip($this->public));
    }

    public static function isPublicEntity($object) : bool
    {
        if (empty($object) === true)
        {
            return false;
        }

        return ($object instanceof self);
    }

    public function getFormattedAmount()
    {
        $currency = $this->getCurrency();

        $currencySymbol = Currency\Currency::SYMBOL[$currency];

        $denominationFactor = Currency\Currency::DENOMINATION_FACTOR[$currency];

        $amount = $this->getAmount() / $denominationFactor;

        $amount = sprintf($amount == intval($amount) ? '%d' : '%.2f', $amount);

        return $currencySymbol . ' ' . $amount;
    }
}
