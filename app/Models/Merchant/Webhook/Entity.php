<?php

namespace RZP\Models\Merchant\Webhook;

use Crypt;

use RZP\Models\Base;
use RZP\Models\Merchant;

/**
 * @property Merchant\Entity $merchant
 */
class Entity extends Base\PublicEntity
{
    const ID                 = 'id';
    const MERCHANT_ID        = 'merchant_id';
    const URL                = 'url';
    const EVENTS             = 'events';
    const ENTITY_TYPE        = 'entity_type';
    const ENTITY_ID          = 'entity_id';
    const FAILURE_COUNT      = 'failure_count';
    const ACTIVE             = 'active';
    const CREATED_AT         = 'created_at';
    const UPDATED_AT         = 'updated_at';
    const SECRET             = 'secret';
    const LAST_SUCCESSFUL_AT = 'last_successful_at';

    // public response const
    const APPLICATION_ID     = 'application_id';

    // for oauth flow checks
    const APPLICATION        = 'application';

    protected $entity       = 'webhook';

    const MAX_FAILURE_COUNT = 3;

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::ACTIVE        => true,
        self::FAILURE_COUNT => 0,
    ];

    protected $fillable = [
        self::URL,
        self::ACTIVE,
        self::EVENTS,
        self::SECRET,
        /*
         * Entity type and id are fillable as in case
         * of OAuth application, `application` and
         * `application_id` are from a different db (auth)
         * and cannot be associated as relations.
         */
        self::ENTITY_TYPE,
        self::ENTITY_ID
    ];

    protected $visible = [
        self::ID,
        self::URL,
        self::EVENTS,
        self::ACTIVE,
        self::MERCHANT_ID,
        self::FAILURE_COUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::SECRET,
        self::LAST_SUCCESSFUL_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::URL,
        self::EVENTS,
        self::ACTIVE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::LAST_SUCCESSFUL_AT,
        self::APPLICATION_ID,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::EVENTS,
        self::APPLICATION_ID,
    ];

    public function edit(array $input = array(), $operation = 'edit')
    {
        parent::edit($input, $operation);

        if ((isset($input[self::ACTIVE])) and
            ($input[self::ACTIVE] === '1'))
        {
            $this->setAttribute(self::FAILURE_COUNT, 0);
        }
    }

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function bumpFailureCount()
    {
        $count = $this->getFailureCount() + 1;

        $this->setFailureCountAttribute($count);
    }

    public function getUrl()
    {
        return $this->getAttribute(self::URL);
    }

    public function getSecret()
    {
        $encryptedSecret = $this->getAttribute(self::SECRET);
        if (!empty($encryptedSecret))
        {
            return Crypt::decrypt($encryptedSecret);
        }
        return null;
    }

    protected function setSecretAttribute($secret)
    {
        if (empty($secret))
        {
            $this->attributes[self::SECRET] = null;
        }
        else
        {
            $encryptedSecret = Crypt::Encrypt($secret);
            $this->attributes[self::SECRET] = $encryptedSecret;
        }
    }

    public function isActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    public function getFailureCount()
    {
        return $this->getAttribute(self::FAILURE_COUNT);
    }

    public function getLastSuccessfulAt()
    {
        return $this->getAttribute(self::LAST_SUCCESSFUL_AT);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getTimeDifferenceFromLastSuccessInHour()
    {
        $lastActive = $this->getAttribute(self::LAST_SUCCESSFUL_AT);

        if ($lastActive === null)
        {
            $lastActive = $this->getAttribute(self::UPDATED_AT);
        }

        $currentTime = time();

        $differenceHours = ($currentTime - $lastActive) / 3600;

        return $differenceHours;
    }

    protected function setEventsAttribute($events)
    {
        $hex = 0;

        if (isset($this->attributes[self::EVENTS]))
        {
            $hex = $this->attributes[self::EVENTS];
        }

        $this->attributes[self::EVENTS] = Event::getHexValue($events, $hex);
    }

    protected function getEventsAttribute()
    {
        $events = $this->attributes[self::EVENTS];

        $enabledEvents = Event::getEnabledEvents($events);

        $names = Event::getLaunchedEventNames();

        $eventsArray = [];

        foreach ($names as $name)
        {
            $eventsArray[$name] = in_array($name, $enabledEvents);
        }

        return $eventsArray;
    }

    public function setPublicEventsAttribute(array & $array)
    {
        $array[self::EVENTS] = Event::filterByFeatures(
                                        $array[self::EVENTS],
                                        $this->merchant->getEnabledFeatures());
    }

    public function setPublicApplicationIdAttribute(array & $array)
    {
        if ($this->getEntityType() === self::APPLICATION)
        {
            $array[self::APPLICATION_ID] = $this->getEntityId();
        }
    }

    public function isEventEnabled($event)
    {
        $hex = $this->getEventsHexValue();

        return Event::isEventEnabled($hex, $event);
    }

    public function resetFailureCount()
    {
        $this->setFailureCountAttribute(0);

        $this->activate();
    }

    public function setLastSuccessfulAt()
    {
        $successfulTime = time();
        $this->setAttribute(self::LAST_SUCCESSFUL_AT, $successfulTime);

        $this->activate();
    }

    protected function getActiveAttribute()
    {
        return (bool) $this->attributes[self::ACTIVE];
    }

    protected function getFailureCountAttribute()
    {
        return (int) $this->attributes[self::FAILURE_COUNT];
    }

    protected function setFailureCountAttribute($count)
    {
        assert ($count <= self::MAX_FAILURE_COUNT);

        $this->attributes[self::FAILURE_COUNT] = $count;

        if ($count === self::MAX_FAILURE_COUNT)
        {
            $this->deactivate();
        }
    }

    protected function getEventsHexValue()
    {
        return $this->attributes[self::EVENTS];
    }

    public function deactivate()
    {
        $this->setAttribute(self::ACTIVE, 0);
    }

    protected function activate()
    {
        $this->setAttribute(self::ACTIVE, 1);
    }
}
