<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Payment;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const PAYMENT_ID                    = 'payment_id';
    const CHECKOUT_ID                   = 'checkout_id';
    const ATTEMPTS                      = 'attempts';
    const LIBRARY                       = 'library';
    const BROWSER                       = 'browser';
    const OS                            = 'os';
    const DEVICE                        = 'device';
    const PLATFORM                      = 'platform';
    const IP                            = 'ip';
    const REFERER                       = 'referer';
    const USER_AGENT                    = 'user_agent';
    const TERMINAL_ID                   = 'terminal_id';
    const TERMINAL_STATUS               = 'terminal_status';
    const TERMINAL_RESPONSE_TIME        = 'terminal_response_time';
    const TERMINAL_STATUS_CODE          = 'terminal_status_code';
    const TERMINAL_STATUS_MSG           = 'terminal_status_msg';
    const PAYMENT_TYPE                  = 'payment_type';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';

    protected $fillable = array(
        self::PAYMENT_ID,
        self::CHECKOUT_ID,
        self::ATTEMPTS,
        self::LIBRARY,
        self::BROWSER,
        self::OS,
        self::DEVICE,
        self::PLATFORM,
        self::IP,
        self::REFERER,
        self::TERMINAL_RESPONSE_TIME,
        self::STATUS_CODE,
        self::STATUS_MSG,
        self::TERMINAL_ID,
        self::TERMINAL_STATUS,
        self::TERMINAL_RESPONSE_TIME,
        self::TERMINAL_STATUS_CODE,
        self::TERMINAL_STATUS_MSG,
        self::PAYMENT_TYPE,
    );

    protected $public = array(
        self::ID,
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::ATTEMPTS,
        self::LIBRARY,
        self::BROWSER,
        self::OS,
        self::DEVICE,
        self::PLATFORM,
        self::IP,
        self::REFERER,
        self::TERMINAL_STATUS,
        self::TERMINAL_RESPONSE_TIME,
        self::TERMINAL_STATUS_CODE,
        self::TERMINAL_STATUS_MSG,
        self::PAYMENT_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected $defaults = array(
        self::LIBRARY     => Metadata::LIBRARY_VALUES[Metadata::DIRECT],
    );

    protected $table = \RZP\Constants\Table::PAYMENT_ANALYTICS;

    protected $generateIdOnCreate = true;

    protected $entity = 'payment_analytics';

    protected static $sign = '';

    protected static $delimiter = '';

    public function getPaymentId()
    {
        return $this->getAttributes(self::PAYMENT_ID);
    }

    public function getCheckoutId()
    {
        return $this->attributes[self::CHECKOUT_ID];
    }

    public function getTerminalId()
    {
        return $this->getAttributes(self::TERMINAL_ID);
    }

    public function getTerminalStatus()
    {
        return $this->getAttributes(self::TERMINAL_STATUS);
    }

    public function getTerminalResponseTime()
    {
        return $this->getAttributes(self::TERMINAL_RESPONSE_TIME);
    }

    public function getAttempts()
    {
        return $this->attributes[self::ATTEMPTS];
    }

    public function getTerminalStatusCode()
    {
        return $this->getAttributes(self::TERMINAL_STATUS_CODE);
    }

    public function getTerminalStatusMsg()
    {
        return $this->getAttributes(self::TERMINAL_STATUS_MSG);
    }

    public function getPaymentType()
    {
        return $this->getAttributes(self::PAYMENT_TYPE);
    }

    public function getLibrary()
    {
        return $this->attributes[self::LIBRARY];
    }

    public function getBrowser()
    {
        return $this->attributes[self::BROWSER];
    }

    public function getOs()
    {
        return $this->attributes[self::OS];
    }

    public function getDevice()
    {
        return $this->attributes[self::DEVICE];
    }

    public function getPlatformAttribute()
    {
        $platform = $this->attributes[self::PLATFORM];

        if ($platform === null)
        {
            return $platform;
        }

        return  Metadata::getStringForPlatformValue($this->attributes[self::PLATFORM]);
    }

    public function getIp()
    {
        return $this->attributes[self::IP];
    }

    public function getReferer()
    {
        return $this->attributes[self::REFERER];
    }

    public function setAttempts($attempts)
    {
        $this->setAttribute(self::ATTEMPTS, $attempts);
    }

    public function setPlatformAttribute($platform)
    {
        $this->attributes[self::PLATFORM] = Metadata::getValueForPlatform($platform);
    }

    public function setCheckoutId($checkoutId)
    {
        $this->setAttribute(self::CHECKOUT_ID, $checkoutId);
    }

    public function setLibrary($library)
    {
        $this->setAttribute(self::LIBRARY, Metadata::getValueForLibrary($library));
    }

    public function setBrowserAttribute($browser)
    {
        $this->attributes[self::BROWSER] = Metadata::getValueForBrowser($browser);
    }

    public function setOsAttribute($os)
    {
        $this->attributes[self::OS] = Metadata::getValueForOs($os);
    }

    public function setDeviceAttribute($device)
    {
        $this->attributes[self::DEVICE] = Metadata::getValueForDevice($device);
    }

    public function setIpAttribute($ip)
    {
        $this->attributes[self::IP] = $ip;
    }

    public function setUserAgentAttribute($ua)
    {
        $this->attributes[self::USER_AGENT] = $ua;
    }

    public function setRefererAttribute($referer)
    {
        $this->attributes[self::REFERER] = $referer;
    }

    public function buildLog($metadata)
    {
        $analyticVal = $this->getValidator();

        $analyticVal->validateInput('metadata', $metadata);

        $this->setPaymentAnalyticData($metadata);
    }
}
