<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment;

/**
 * @property-read Payment\Entity $payment
 */
class Entity extends Base\PublicEntity
{
    const PAYMENT_ID                    = 'payment_id';
    const MERCHANT_ID                   = 'merchant_id';
    const CHECKOUT_ID                   = 'checkout_id';
    const RISK_SCORE                    = 'risk_score';
    const RISK_ENGINE                   = 'risk_engine';
    const ATTEMPTS                      = 'attempts';
    const LIBRARY                       = 'library';
    const LIBRARY_VERSION               = 'library_version';
    const BROWSER                       = 'browser';
    const BROWSER_VERSION               = 'browser_version';
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
    const VIRTUAL_DEVICE_ID             = 'virtual_device_id';

    protected $entity = 'payment_analytics';

    protected $primaryKey = 'payment_id';

    const SEARCH_WINDOW = 60 * 60 * 24;

    protected $fillable = [
        self::CHECKOUT_ID,
        self::ATTEMPTS,
        self::LIBRARY,
        self::LIBRARY_VERSION,
        self::BROWSER,
        self::BROWSER_VERSION,
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
    ];

    protected $public = [
        self::PAYMENT_ID,
        self::MERCHANT_ID,
        self::CHECKOUT_ID,
        self::ATTEMPTS,
        self::LIBRARY,
        self::LIBRARY_VERSION,
        self::BROWSER,
        self::BROWSER_VERSION,
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
    ];

    protected $visible = [
        self::PAYMENT_ID,
        self::MERCHANT_ID,
        self::CHECKOUT_ID,
        self::RISK_SCORE,
        self::RISK_ENGINE,
        self::ATTEMPTS,
        self::LIBRARY,
        self::LIBRARY_VERSION,
        self::BROWSER,
        self::BROWSER_VERSION,
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
    ];

    protected $casts = [
        self::ATTEMPTS   => 'int',
        self::RISK_SCORE => 'float',
    ];

    // ----------------------- Relations ---------------------------------------

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    // ----------------------- Getters -----------------------------------------

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

    public function getBrowserVersion()
    {
        return $this->getAttribute(self::BROWSER_VERSION);
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

    public function getUserAgent()
    {
        return $this->getAttribute(self::USER_AGENT);
    }

    // ----------------------- Getters End -------------------------------------

    // ----------------------- Setters -----------------------------------------

    public function setAttempts($attempts)
    {
        $this->setAttribute(self::ATTEMPTS, $attempts);
    }

    public function setCheckoutId($checkoutId)
    {
        $this->setAttribute(self::CHECKOUT_ID, $checkoutId);
    }

    public function setBrowserVersion($browserVersion)
    {
        $this->setAttribute(self::BROWSER_VERSION, $browserVersion);
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

    public function setIntegration($integration)
    {
        $this->setAttribute(self::INTEGRATION, $integration);
    }

    public function setLibrary($library)
    {
        $this->setAttribute(self::LIBRARY, $library);
    }

    public function setBrowser($browser)
    {
        $this->setAttribute(self::BROWSER, $browser);
    }

    public function setPlatform($platform)
    {
        $this->setAttribute(self::PLATFORM, $platform);
    }

    public function setOs($os)
    {
        $this->setAttribute(self::OS, $os);
    }

    public function setDevice($device)
    {
        $this->setAttribute(self::DEVICE, $device);
    }

    public function setIp($ip)
    {
        $this->setAttribute(self::IP, $ip);
    }

    public function setReferer($referer)
    {
        $this->setAttribute(self::REFERER, $referer);
    }

    public function setUserAgent($ua)
    {
        $this->setAttribute(self::USER_AGENT, $ua);
    }

    public function setMerchantId($merchant_id)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchant_id);
    }

    public function setRiskScore($score)
    {
        $this->setAttribute(self::RISK_SCORE, $score);
    }

    public function setRiskEngine($riskEngine)
    {
        $this->setAttribute(self::RISK_ENGINE, $riskEngine);
    }

    public function setVirtualDeviceId($deviceId)
    {
        $this->setAttribute(self::VIRTUAL_DEVICE_ID, $deviceId);
    }

    // ----------------------- Setters End--------------------------------------

    // ----------------------- Accessors ---------------------------------------

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

    public function getRiskScore()
    {
        return $this->getAttribute(self::RISK_SCORE);
    }

    public function getRiskEngine()
    {
        return $this->getAttribute(self::RISK_ENGINE);
    }

    public function getVirtualDeviceId()
    {
        return $this->getAttribute(self::VIRTUAL_DEVICE_ID);
    }
    protected function getRiskEngineAttribute()
    {
        $value = $this->attributes[self::RISK_ENGINE];

        return Metadata::getStringForValue($value, Metadata::RISK_ENGINE_VALUES);
    }

    // ----------------------- Accessors End -----------------------------------

    // ----------------------- Mutators ----------------------------------------

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

    protected function setRiskEngineAttribute($engine)
    {
        $this->attributes[self::RISK_ENGINE] = Metadata::getValueForRiskEngine($engine);
    }

    // ----------------------- Mutators End ------------------------------------
}
