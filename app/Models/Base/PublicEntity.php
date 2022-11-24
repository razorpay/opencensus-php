<?php

namespace RZP\Models\Base;

use App;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Currency;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Transaction;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode as TraceCode;

/**
 * @property Transaction\Entity $transaction
 * @property Merchant\Entity    $merchant
 */
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

    const SIGNED_PUBLIC_ID_REGEX   = '/\b[a-z]{0,6}_[a-zA-Z0-9]{14}\b/';

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

    /**
     * Fields to be returned when receiving expand[] query param in fetch
     * (eg. transaction, transaction.settlement with payment fetch)
     *
     * @var array
     */
    protected $expanded         = [];

    /**
     * This variable name cannot be "customer" since
     * some entities like subscriptions have a relation
     * named customer already which is accessed by magic method.
     * That clashes. Hence, using publicCustomer instead.
     *
     * This is mainly used right now for displaying refund
     * details to the customer.
     *
     * @var array
     */
    protected $publicCustomer      = [];

    /**
     * This is used to expose only specific fields when `toArrayPublic`
     * is being called from public auth.
     *
     * @var array
     */
    protected $publicAuth = [];

    /**
     * This is used for displaying payments for certain
     * heimdall orgs' admins that use heimdall internally
     * in a restricted fashion. They see a limited subset
     * of admin attributes.
     *
     * @var array
     */
    protected $adminRestricted     = [];

    /**
     * This is used for maintaining config where admin attributes
     * are to be limited based on feature flag on orgs
     *
     * @var array
     */
    protected $adminRestrictedWithFeature = [];

    protected $publicSetters    = [
        self::ID,
        self::ENTITY,
    ];

    protected $embeddedRelations = [];

    /**
     * Fields exposed to public but only in AdminAuth. Please don't overuse it since this will removed anyways in a
     * cleanup effort after Entity Serializer PR is merged.
     *
     * @var array
     */
    protected $adminOnlyPublic   = [];

    /**
     * Fields exposed through the toArrayPartner() function.
     *
     * @var array
     */
    protected $partner           = [];

    /**
     * Fields exposed through the toArrayCaPartnerBankPoc() function.
     *
     * @var array
     */
    protected $bankBranchPoc     = [];

    /**
     * Fields exposed through the toArrayCaPartnerBankManager() function.
     *
     * @var array
     */
    protected $bankBranchManager = [];

    protected $reconAppInternal  = [];

    public function toArrayPublic()
    {
        $attributes = $this->attributesToArray();

        $relations = $this->relationsToArrayPublic();

        $array = array_merge($attributes, $relations);

        $this->setPublicAttributes($array);

        return $this->arrangePublicAttributes($array);
    }

    public function toArrayInternal()
    {
        $attributes = $this->attributesToArray();

        $relations = $this->relationsToArrayPublic();

        $array = array_merge($attributes, $relations);

        $this->setInternalAttributes($array);

        return $this->arrangeInternalAttributes($array);
    }

    public function toArrayAudit(bool $expand = false)
    {
        $attributes = $this->attributesToArray();

        $relations = $this->relationsToArrayPublic($expand);

        return array_merge($attributes, $relations);
    }

    /**
     * Returns relations with public array based on expand[] query param in
     * fetch routes (eg. transaction, transaction.settlement with payment fetch),
     * as these relations might not be in public array of the fetched entity.
     *
     * @return  array
     */
    public function toArrayPublicWithExpand()
    {
        $attributes = $this->attributesToArray();

        $relations = $this->relationsToArrayPublic(true);

        $array = array_merge($attributes, $relations);

        $this->setPublicAttributes($array);

        $publicArray = $this->arrangePublicAttributes($array);

        $this->arrangeExpandAttributes($array, $publicArray);

        return $publicArray;
    }

    /**
     * toArrayPublic at times has fields that we set/unset based on auth type. This should not impact the webhook data.
     * toArrayWebhook assumes that the $webhook array will be a subset of the $public array.
     * This function uses the public setters itself for custom data but only returns fields present in $webhook array.
     *
     * @return array
     */
    public function toArrayWebhook()
    {
        $attributes = $this->toArrayPublic();

        return array_only($attributes, $this->webhook);
    }

    public function toArrayAdmin()
    {
        $app = App::getFacadeRoot();

        /** @var BasicAuth $ba */
        $ba = $app['basicauth'];

        $attributes = $this->attributesToArray();

        $relations = $this->relationsToArrayAdmin();

        $array = array_merge($attributes, $relations);

        $this->setPublicAttributes($array);

        // TODO: Check if this is needed for restricted orgs
        $array[static::ADMIN] = true;

        if ($ba->getOrgType() === Org\Entity::RESTRICTED)
        {
            return $this->toArrayAdminRestricted($array);
        }

        return $array;
    }

    public function toArrayAdminRestricted(array $array)
    {
        return array_only($array, $this->adminRestricted);
    }

    public function toArrayAdminRestrictedWithFeature(array $array, $orgType, $orgFeature)
    {
        /* if orgType is restricted, will fetch restricted array in adminRestricted
        if orgType is null (not restricted), will fetch adminRestricted according to feature flag */
        if($orgType === null)
        {
            if( ($orgFeature !== null) and
                (isset($this->adminRestrictedWithFeature[$orgFeature]) === true) )
            {
                return array_only($array, $this->adminRestrictedWithFeature[$orgFeature]);
            }
        }

        return array_only($array, $this->adminRestricted);
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

        if ($array === null)
        {
            return null;
        }

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

    /**
     * toArrayPartner() comprises of all Public attributes and a few additional attributes exposed only to the partners.
     *
     * @return array
     */
    public function toArrayPartner(): array
    {
        $arrayAttributes = $this->attributesToArray();

        $partnerAttributes = array_only($arrayAttributes, $this->partner);

        $publicAttributes = $this->toArrayPublic();

        $array = array_merge($publicAttributes, $partnerAttributes);

        return $array;
    }

    public function toArrayCaPartnerBankPoc(): array
    {
        $arrayAttributes = $this->attributesToArray();

        $partnerAttributes = array_only($arrayAttributes, $this->partner);

        $publicAttributes = $this->toArrayPublic();

        $array = array_merge($publicAttributes, $partnerAttributes);

        return $array;
    }

    public function toArrayCaPartnerBankManager(): array
    {
        $arrayAttributes = $this->attributesToArray();

        $partnerAttributes = array_only($arrayAttributes, $this->partner);

        $publicAttributes = $this->toArrayPublic();

        $array = array_merge($publicAttributes, $partnerAttributes);

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

    public function toArrayPublicCustomer(bool $populateMessages = false)
    {
        $attributes = $this->toArrayPublic();

        return array_only($attributes, $this->publicCustomer);
    }

    public function toArrayRecon()
    {
        $attributes = $this->toArrayPublic();

        return array_only($attributes, $this->reconAppInternal);
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

    public function setInternalAttributes(array & $array)
    {
        foreach ($this->internalSetters as $attr)
        {
            $func = 'setInternal' . studly_case($attr) . 'Attribute';

            $this->$func($array);
        }
    }

    /**
     * Relations of the fetched entity.
     *
     *@param bool $expand
     *@return array
     */
    public function relationsToArrayPublic(bool $expand = false)
    {
        $public = array_flip($this->public);

        if ($expand === true)
        {
            $expanded = array_flip($this->expanded);

            $public = array_merge($public, $expanded);
        }

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
                    $array[$key] = $value->toArrayPublicEmbedded($expand);
                }
                else if ($expand === true)
                {
                    $array[$key] = $value->toArrayPublicWithExpand();
                }
                else
                {
                    $array[$key] = $value->toArrayPublic();
                }
            }
            else if (static::isPublicEntity($value) === true)
            {
                if ($expand === true)
                {
                    $array[$key] = $value->toArrayPublicWithExpand();
                }
                else
                {
                    $array[$key] = $value->toArrayPublic();
                }
            }
            else
            {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    public function relationsToArrayAdmin()
    {
        //
        // If you're not getting relations data here,
        // Add the relation as the visible array in the Entity as camel cased
        // Eg: bankingAccountDetails in $visible of BankingAccount\Detail\Entity
        //
        $relations = $this->getArrayableRelations();

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

        $array = [];

        foreach ($relations as $key => $value)
        {
            if ((PublicCollection::isPublicCollection($value) === true) or (static::isPublicEntity($value) === true))
            {
                $array[$key] = $value->toArrayAdmin();
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

    public function setInternalIdAttribute(array & $array)
    {
        $this->setPublicIdAttribute($array);
    }

    public function setPublicEntityAttribute(array & $array)
    {
        $array[static::ENTITY] = $this->entity;
    }

    public function setInternalEntityAttribute(array & $array)
    {
        $this->setPublicEntityAttribute($array);
    }

    public function arrangeExpandAttributes(array $array, array & $publicArray)
    {
        foreach ($this->expanded as $expandedAttr)
        {
            if (array_key_exists($expandedAttr, $array) === true)
            {
                $publicArray[$expandedAttr] = $array[$expandedAttr];
            }
        }

        $this->authRelatedChecks($publicArray);
    }

    public function arrangePublicAttributes(array $array)
    {
        $publicArray = [];

        foreach ($this->public as $attr)
        {
            if (array_key_exists($attr, $array))
            {
                $publicArray[$attr] = $array[$attr];
            }
        }

        $this->authRelatedChecks($publicArray);

        return $publicArray;
    }

    public function arrangeInternalAttributes(array $array)
    {
       return $this->arrangePublicAttributes($array);
    }

    protected function authRelatedChecks(array & $publicArray)
    {
        $app = App::getFacadeRoot();

        if ($app['basicauth']->isAdminAuth() === false)
        {
            foreach ($this->adminOnlyPublic as $attr)
            {
                unset($publicArray[$attr]);
            }
        }

        if (($app['basicauth']->isPublicAuth() === true) and
            (empty($this->publicAuth) === false))
        {
            $publicArray = array_only($publicArray, $this->publicAuth);
        }
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
        $url = "https://dashboard.razorpay.com/admin#/app/entity/$entity/live/$id";

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
            $attributes = $this->getRawOriginal();

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

    public static function silentlyStripSign(& $id)
    {
        static::stripSign($id);

        return $id;
    }

    public static function verifyIdAndStripSignMultiple(array & $ids)
    {
        $newIds = array_map(function($id)
        {
            return static::verifyIdAndStripSign($id);
        }, $ids);

        $ids = $newIds;

        return $newIds;
    }

    public static function verifyIdAndSilentlyStripSignMultiple(array & $ids)
    {
        $newIds = array_map(function($id)
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
        $newIds = array_map(function($id)
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

    /**
     * 12012(in paise) as  ['₹',120, 12] (rupees paise as separate entry in array)
     *
     * @param bool $dcc
     * @return array
     */
    public function getAmountComponents($dcc = false): array
    {
        $currency = $this->getCurrency();
        $amount = $this->getAmount();

        if($dcc === true) {
            $currency = $this->getGatewayCurrency();
            $amount = $this->getGatewayAmount();
        }

        $currencySymbol = Currency\Currency::SYMBOL[$currency] ?: 'INR';

        $denominationFactor = Currency\Currency::DENOMINATION_FACTOR[$currency] ?: 100;

        $superUnitInAmount = money_format_IN((integer)($amount / $denominationFactor));

        $subUnitInAmount = str_pad($amount % $denominationFactor, 2, 0, STR_PAD_LEFT);

        return [$currencySymbol, $superUnitInAmount, $subUnitInAmount];
    }

    public function getFormattedAmountsAsPerCurrency(string $currency = null, int $amount = null)
    {
        if ($currency === null)
        {
            $currency = $this->getCurrency();
        }

        $currencySymbol = Currency\Currency::SYMBOL[$currency] ?? '₹';
        $denominationFactor = Currency\Currency::DENOMINATION_FACTOR[$currency] ?? 100;

        if ($amount === null)
        {
            $amount = $this->getAmount();
        }

        $amount = $amount / $denominationFactor;
        $amount = sprintf($amount === intval($amount) ? '%d' : '%.2f', $amount);

        return $currencySymbol . ' ' . $amount;
    }

    /**
     * This method extracts orgID from entity and if any exceptions occur handle that gracefully.
     * @return string
     */
    public function getMerchantOrgId()
    {
        $app = App::getFacadeRoot();

        try
        {
            return $this->getOrgId();
        }
        catch (\Exception $exception) {}

        try
        {
            return $this->merchant->getOrgId();
        }
        catch (\Exception $exception)
        {
            $app['trace']->traceException($exception, Trace::INFO, TraceCode::FETCHING_ORG_ID_FAILED);

            return '';
        }
    }
}
