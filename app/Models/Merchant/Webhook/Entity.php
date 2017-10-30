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
    const FAILURE_COUNT      = 'failure_count';
    const ACTIVE             = 'active';
    const CREATED_AT         = 'created_at';
    const UPDATED_AT         = 'updated_at';
    const SECRET             = 'secret';
    const LAST_SUCCESSFUL_AT = 'last_successful_at';

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
        self::SECRET
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
        self::LAST_SUCCESSFUL_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::URL,
        self::EVENTS,
        self::ACTIVE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::LAST_SUCCESSFUL_AT
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::EVENTS,
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
        $featureMap = Event::$eventsToFeatureMap;

        $assignedFeatures = $this->merchant->getEnabledFeatures();

        foreach ($array[self::EVENTS] as $event => $value)
        {
            if (isset($featureMap[$event]) === false)
            {
                continue;
            }

            if (in_array($featureMap[$event], $assignedFeatures, true) === false)
            {
                unset($array[self::EVENTS][$event]);
            }
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
