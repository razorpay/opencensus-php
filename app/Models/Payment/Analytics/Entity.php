<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const PAYMENT_ID                    = 'payment_id';
    const CHECKOUT_ID                   = 'checkout_id';
    const ATTEMPTS                      = 'attempts';
    const LIBRARY                       = 'library';
    const LIBRARY_VERSION               = 'library_version';
    const BROWSER                       = 'browser';
    const OS                            = 'os';
    const OS_VERSION                    = 'os_version';
    const DEVICE                        = 'device';
    const PLATFORM                      = 'platform';
    const PLATFORM_VERSION              = 'platform_version';
    const INTEGRATION                   = 'integration';
    const INTEGRATION_VERSION           = 'integration_version';
    const IP                            = 'ip';
    const REFERER                       = 'referer';
    const USER_AGENT                    = 'user_agent';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';

    // window in secs, used to fetch payments with same checkout id
    const PAYMENT_WINDOW                = 1800;

    protected $table = Table::PAYMENT_ANALYTICS;

    protected $entity = 'payment_analytics';

    protected static $sign = '';

    protected static $delimiter = '';

    protected $fillable = array(
        self::PAYMENT_ID,
        self::CHECKOUT_ID,
        self::ATTEMPTS,
        self::LIBRARY,
        self::LIBRARY_VERSION,
        self::BROWSER,
        self::OS,
        self::OS_VERSION,
        self::DEVICE,
        self::PLATFORM,
        self::PLATFORM_VERSION,
        self::IP,
        self::INTEGRATION,
        self::INTEGRATION_VERSION,
        self::REFERER,
        self::USER_AGENT,
    );

    protected $public = array(
        self::ID,
        self::PAYMENT_ID,
        self::CHECKOUT_ID,
        self::ATTEMPTS,
        self::LIBRARY,
        self::LIBRARY_VERSION,
        self::BROWSER,
        self::OS,
        self::OS_VERSION,
        self::DEVICE,
        self::PLATFORM,
        self::PLATFORM_VERSION,
        self::IP,
        self::INTEGRATION,
        self::INTEGRATION_VERSION,
        self::REFERER,
        self::USER_AGENT,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected static $modifiers = array(
        self::OS,
    );

    protected $casts = array(
        self::ATTEMPTS               => 'int',
    );

    // ----------------------- Relations -------------------------------------------

    public function payment()
    {
        return $this->hasOne('RZP\Models\Payment\Entity');
    }

    // ----------------------- Getters ---------------------------------------------

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getCheckoutId()
    {
        return $this->getAttribute(self::CHECKOUT_ID);
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function getLibrary()
    {
        return $this->getAttribute(self::LIBRARY);
    }

    public function getLibraryVersion()
    {
        return $this->getAttribute(self::LIBRARY_VERSION);
    }

    public function getBrowser()
    {
        return $this->getAttribute(self::BROWSER);
    }

    public function getOs()
    {
        return $this->getAttribute(self::OS);
    }

    public function getOsVersion()
    {
        return $this->getAttribute(self::OS_VERSION);
    }

    public function getDevice()
    {
        return $this->getAttribute(self::DEVICE);
    }

    public function getPlatform()
    {
        return $this->getAttribute(self::PLATFORM);
    }

    public function getPlatformVersion()
    {
        return $this->getAttribute(self::PLATFORM_VERSION);
    }

    public function getIp()
    {
        return $this->getAttribute(self::IP);
    }

    public function getReferer()
    {
        return $this->getAttribute(self::REFERER);
    }

    public function getIntegration()
    {
        return $this->getAttribute(self::INTEGRATION);
    }

    public function getIntegrationVersion()
    {
        return $this->getAttribute(self::INTEGRATION_VERSION);
    }

    // ----------------------- Getters End ---------------------------------------------

    // ----------------------- Setters ---------------------------------------------

    public function setAttempts($attempts)
    {
        $this->setAttribute(self::ATTEMPTS, $attempts);
    }

    public function setCheckoutId($checkoutId)
    {
        $this->setAttribute(self::CHECKOUT_ID, $checkoutId);
    }

    public function setPlatformVersion($platformVersion)
    {
        $this->setAttribute(self::PLATFORM_VERSION, $platformVersion);
    }

    public function setLibraryVersion($libraryVersion)
    {
        $this->setAttribute(self::LIBRARY_VERSION, $libraryVersion);
    }

    public function setOsVersion($osVersion)
    {
        $this->setAttribute(self::OS_VERSION, $osVersion);
    }

    public function setIntegrationVersion($integrationVersion)
    {
        $this->setAttribute(self::INTEGRATION_VERSION, $integrationVersion);
    }

    // ----------------------- Setters End---------------------------------------------

    // ----------------------- Mutator ---------------------------------------------
    //

    protected function getLibraryAttribute()
    {
        $value = $this->attributes[self::LIBRARY];

        return Metadata::getStringForValue($value, Metadata::LIBRARY_VALUES);
    }

    protected function getPlatformAttribute()
    {
        $value = $this->attributes[self::PLATFORM];

        return Metadata::getStringForValue($value, Metadata::PLATFORM_VALUES);
    }

    protected function getBrowserAttribute()
    {
        $value = $this->attributes[self::BROWSER];

        return Metadata::getStringForValue($value, Metadata::BROWSER_VALUES);
    }

    protected function getOsAttribute()
    {
        $value = $this->attributes[self::OS];

        return Metadata::getStringForValue($value, Metadata::OS_VALUES);
    }

    protected function getDeviceAttribute()
    {
        $value = $this->attributes[self::DEVICE];

        return Metadata::getStringForValue($value, Metadata::DEVICE_VALUES);
    }

    protected function getIntegrationAttribute()
    {
        $value = $this->attributes[self::INTEGRATION];

        return Metadata::getStringForValue($value, Metadata::INTEGRATION_VALUES);
    }

    protected function setPlatformAttribute($platform)
    {
        $this->attributes[self::PLATFORM] = Metadata::getValueForPlatform($platform);
    }

    protected function setLibraryAttribute($library)
    {
        $this->attributes[self::LIBRARY] = Metadata::getValueForLibrary($library);
    }

    protected function setBrowserAttribute($browser)
    {
        $this->attributes[self::BROWSER] = Metadata::getValueForBrowser($browser);
    }

    protected function setOsAttribute($os)
    {
        $this->attributes[self::OS] = Metadata::getValueForOs($os);
    }

    protected function setIntegrationAttribute($integration)
    {
        $this->attributes[self::INTEGRATION] = Metadata::getValueForIntegration($integration);
    }

    protected function setDeviceAttribute($device)
    {
        $this->attributes[self::DEVICE] = Metadata::getValueForDevice($device);
    }

    protected function setIpAttribute($ip)
    {
        $this->attributes[self::IP] = $ip;
    }

    protected function setUserAgentAttribute($ua)
    {
        $this->attributes[self::USER_AGENT] = $ua;
    }

    protected function setRefererAttribute($referer)
    {
        $this->attributes[self::REFERER] = $referer;
    }

    // ----------------------- Mutator Ends ----------------------------------------

    // ----------------------- Modifiers ------------------------------------------

    protected function modifyOs(& $input)
    {
        if ((isset($input[self::OS])) and
            (strtolower($input[self::OS]) === 'os x'))
        {
            $input[self::OS] = Metadata::MACOS;
        }
    }
}
